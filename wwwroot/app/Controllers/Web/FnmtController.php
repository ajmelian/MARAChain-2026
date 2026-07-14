<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Services\FnmtIdentityProvider;
use App\Services\X509Service;
use App\Models\EvidenceModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * FnmtController — FNMT certificate authentication.
 *
 * Implements CU-AUTH-001 (first access) and CU-AUTH-002 (recurring).
 * The certificate is validated by Nginx via mTLS. This controller
 * extracts identity from request headers and manages the TOTP
 * enrollment flow for new users.
 *
 * Flow:
 *   Nginx mTLS → extract NIF → find/create user → check TOTP →
 *   if first access: show TOTP QR enrollment
 *   if recurring: validate TOTP code → create SHIELD session
 *
 * @package App\Controllers\Web
 * @author  Aythami
 * @since   1.4.0
 */
class FnmtController extends BaseWebController
{
    private X509Service $x509;

    private FnmtIdentityProvider $fnmtProvider;

    private EvidenceModel $evidenceModel;

    private UserModel $userModel;

    public function __construct()
    {
        $this->x509           = new X509Service();
        $this->fnmtProvider   = new FnmtIdentityProvider();
        $this->evidenceModel  = model(EvidenceModel::class);
        $this->userModel      = model(UserModel::class);
    }

    // ═════════════════════════════════════════════════════════════════
    //  CERTIFICATE LOGIN — CU-AUTH-001 / CU-AUTH-002
    // ═════════════════════════════════════════════════════════════════

    /**
     * GET /auth/fnmt — FNMT certificate login entry point.
     *
     * Nginx handles the mTLS handshake. This controller:
     *   1. Checks SSL_CLIENT_VERIFY = SUCCESS
     *   2. Extracts identity from SSL_CLIENT_S_DN
     *   3. Finds or creates the UserIdentity
     *   4. If new user → redirect to TOTP enrollment
     *   5. If existing → redirect to TOTP verification
     *
     * @return ResponseInterface|string Redirect or HTML
     *
     * @since 1.4.0
     */
    public function login(): ResponseInterface|string
    {
        $userAgent = substr((string) $this->request->getUserAgent(), 0, 255);

        // ── Step 1: Extract identity from Nginx headers ──────────
        try {
            $identity = $this->fnmtProvider->resolveIdentity([
                'server' => $this->request->getServer(),
            ]);
        } catch (\RuntimeException $e) {
            $this->recordEvidence('FnmtAuthFailed', [
                'reason' => $e->getMessage(),
            ], $userAgent);

            return $this->render('auth/fnmt_error', [
                'title'   => 'Error de autenticacion',
                'error'   => $e->getMessage(),
            ]);
        }

        $taxId  = $identity['taxId'];
        $hmacKey = env('encryption.hmacKey');

        if (empty($hmacKey)) {
            throw new \RuntimeException(
                'encryption.hmacKey is not configured. FNMT authentication cannot proceed.'
            );
        }

        // ── Regenerate session to prevent session fixation ─────────
        session()->regenerate(true);

        $taxIdHmac = hash_hmac('sha256', $taxId, $hmacKey);

        // ── Step 2: Find or create custom user identity ──────────
        $customUser = $this->userModel->findByTaxIdHmac($taxIdHmac);

        if ($customUser === null) {
            // ── New user: create custom profile ──────────────────
            $userId = \App\Helpers\Uuid::v4();

            $this->userModel->create([
                'id'             => $userId,
                'identityType'   => $identity['identityType'],
                'firstName'      => $identity['firstName'],
                'lastName'       => $identity['lastName'],
                'legalName'      => $identity['legalName'],
                'email'          => $taxId . '@fnmt.local',
                'taxIdHmac'      => $taxIdHmac,
                'status'         => 'active',
                'guaranteeLevel' => $identity['guaranteeLevel'],
            ]);

            $this->recordEvidence('UserCreatedViaFnmt', [
                'taxIdHmac'     => $taxIdHmac,
                'identityType'  => $identity['identityType'],
                'guaranteeLevel'=> $identity['guaranteeLevel'],
            ], $userAgent);

            // ── Redirect to TOTP enrollment ──────────────────────
            session()->set('fnmt_user_id', $userId);
            session()->set('fnmt_tax_id_hmac', $taxIdHmac);

            return redirect()->to('/auth/fnmt/totp-setup')
                ->with('message', 'Identidad verificada. Configure el segundo factor.');
        }

        // ── Existing user ────────────────────────────────────────
        $customUserModel = $this->userModel;
        $customUserModel->update($customUser->id, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $this->recordEvidence('FnmtLoginSuccess', [
            'taxIdHmac' => $taxIdHmac,
            'userId'    => $customUser->id,
        ], $userAgent);

        // ── Check TOTP status ────────────────────────────────────
        $freshUser = $this->userModel->find($customUser->id);

        if ($freshUser && $freshUser->totpEnabled) {
            session()->set('fnmt_user_id', $customUser->id);

            return redirect()->to('/auth/fnmt/totp-verify')
                ->with('message', 'Introduzca el codigo TOTP.');
        }

        // ── TOTP not configured → enroll ─────────────────────────
        session()->set('fnmt_user_id', $customUser->id);

        return redirect()->to('/auth/fnmt/totp-setup')
            ->with('message', 'Configure la autenticacion en dos pasos.');
    }

    // ═════════════════════════════════════════════════════════════════
    //  TOTP ENROLLMENT
    // ═════════════════════════════════════════════════════════════════

    /**
     * GET /auth/fnmt/totp-setup — Show TOTP QR code enrollment.
     *
     * POST /auth/fnmt/totp-setup — Validate first TOTP code.
     *
     * @return ResponseInterface|string
     *
     * @since 1.4.0
     */
    public function totpSetup(): ResponseInterface|string
    {
        $userId = session()->get('fnmt_user_id');

        if ($userId === null) {
            return redirect()->to('/login')
                ->with('error', 'Sesion expirada. Inicie de nuevo.');
        }

        if ($this->request->getMethod() === 'GET') {
            $user = $this->userModel->find($userId);

            // ── Generate TOTP secret ─────────────────────────────
            $secret     = $this->generateTotpSecret();
            $issuer     = 'MARAChain';
            $accountName = $user ? ($user->email ?? 'user') : 'user';
            $qrUrl      = sprintf(
                'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
                rawurlencode($issuer),
                rawurlencode($accountName),
                $secret,
                rawurlencode($issuer)
            );

            session()->set('fnmt_totp_secret', $secret);

            return $this->render('auth/fnmt_totp_setup', [
                'title'       => 'Configurar autenticacion en dos pasos',
                'qrCodeUrl'   => $qrUrl,
                'secret'      => $secret,
            ]);
        }

        // ── POST: Validate TOTP code ─────────────────────────────
        $code   = $this->request->getPost('totp_code');
        $secret = session()->get('fnmt_totp_secret');

        if (! $this->verifyTotp($secret, $code)) {
            return redirect()->back()
                ->with('error', 'Codigo TOTP invalido. Intente de nuevo.');
        }

        // ── Store TOTP secret and enable ─────────────────────────
        $user = $this->userModel->find($userId);

        if ($user) {
            $encryptedSecret = $this->encryptTotpSecret($secret);
            $this->userModel->enableTotp($user, $encryptedSecret);

            $this->recordEvidence('TotpEnrolled', [
                'userId' => $userId,
            ]);
        }

        // ── Clear FNMT session and redirect to complete auth ─────
        session()->remove('fnmt_totp_secret');

        return redirect()->to('/auth/fnmt/totp-verify')
            ->with('message', 'TOTP configurado. Verifique con un codigo.');
    }

    // ═════════════════════════════════════════════════════════════════
    //  TOTP VERIFICATION (Recurring access — CU-AUTH-002)
    // ═════════════════════════════════════════════════════════════════

    /**
     * GET /auth/fnmt/totp-verify — Show TOTP code input.
     *
     * POST /auth/fnmt/totp-verify — Validate TOTP and create session.
     *
     * @return ResponseInterface|string
     *
     * @since 1.4.0
     */
    public function totpVerify(): ResponseInterface|string
    {
        $userId = session()->get('fnmt_user_id');

        if ($userId === null) {
            return redirect()->to('/login')
                ->with('error', 'Sesion expirada.');
        }

        if ($this->request->getMethod() === 'GET') {
            return $this->render('auth/fnmt_totp_verify', [
                'title' => 'Verificar codigo TOTP',
            ]);
        }

        // ── POST: Validate TOTP ──────────────────────────────────
        $code = $this->request->getPost('totp_code');
        $user = $this->userModel->find($userId);

        if ($user === null) {
            return redirect()->to('/login')
                ->with('error', 'Usuario no encontrado.');
        }

        // ── Check TOTP failures and blocked status ───────────────
        if ($user->isTotpBlocked()) {
            return redirect()->to('/login')
                ->with('error', 'TOTP bloqueado temporalmente. Espere 30 minutos.');
        }

        $secret = $this->decryptTotpSecret($user->totpSecretEncrypted ?? '');

        if (! $this->verifyTotp($secret, $code)) {
            // Increment failures and check for block
            $updated = $this->userModel->incrementTotpFailures($user);

            $msg = 'Codigo TOTP invalido.';

            if ($updated->isBlocked()) {
                $msg = 'Demasiados intentos. TOTP bloqueado por 30 minutos.';
            }

            return redirect()->back()->with('error', $msg);
        }

        // ── Reset failures on success ────────────────────────────
        $this->userModel->resetTotpFailures($user);

        // ── Regenerate session to prevent fixation ────────────────
        session()->regenerate(true);

        // ── Create SHIELD session for the user ───────────────────
        $this->createShieldSessionForUser($user);

        return redirect()->to('/inbox')
            ->with('message', 'Autenticacion completada.');
    }

    // ═════════════════════════════════════════════════════════════════
    //  HELPERS
    // ═════════════════════════════════════════════════════════════════

    /**
     * Generate a random Base32 TOTP secret.
     */
    private function generateTotpSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * Verify a TOTP code against a secret.
     *
     * Uses 30-second window with 1-step tolerance.
     */
    private function verifyTotp(string $secret, string $code): bool
    {
        if (empty($secret) || strlen($code) !== 6) {
            return false;
        }

        $base32 = $this->base32Decode($secret);
        $timeSlice = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            $expected = $this->computeTotp($base32, $timeSlice + $i);

            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute a TOTP code for a given time slice.
     */
    private function computeTotp(string $key, int $timeSlice): string
    {
        $time = pack('N', 0) . pack('N', $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a Base32 string to binary.
     */
    private function base32Decode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input    = rtrim(strtoupper($input), '=');
        $binary   = '';
        $buffer   = 0;
        $bits     = 0;

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $pos = strpos($alphabet, $input[$i]);

            if ($pos === false) {
                return '';
            }

            $buffer = ($buffer << 5) | $pos;
            $bits += 5;

            if ($bits >= 8) {
                $bits -= 8;
                $binary .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $binary;
    }

    /**
     * Encrypt TOTP secret for storage using AES-256-GCM.
     *
     * Uses the application encryption key (encryption.key) for AEAD.
     * Produces base64-encoded ciphertext with IV and tag prepended.
     *
     * @param string $secret Plaintext Base32 TOTP secret
     *
     * @return string Base64-encoded encrypted secret (IV + ciphertext + tag)
     *
     * @throws \RuntimeException When encryption key is not configured
     *
     * @since 1.4.0
     */
    private function encryptTotpSecret(string $secret): string
    {
        $rawKey = env('encryption.key');

        if (empty($rawKey)) {
            throw new \RuntimeException(
                'encryption.key is not configured. TOTP secret cannot be encrypted.'
            );
        }

        // Derive a 256-bit key from the configured encryption key
        $key = hash('sha256', $rawKey, true);
        $iv  = random_bytes(12); // 96-bit nonce for GCM

        $ciphertext = openssl_encrypt(
            $secret,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // tag length
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('TOTP secret encryption failed.');
        }

        // Format: IV (12 bytes) + ciphertext + tag (16 bytes)
        return base64_encode($iv . $ciphertext . $tag);
    }

    /**
     * Decrypt TOTP secret from storage.
     *
     * Reverses the AES-256-GCM encryption applied by encryptTotpSecret().
     *
     * @param string $encrypted Base64-encoded encrypted secret
     *
     * @return string Plaintext Base32 TOTP secret, or empty string on failure
     *
     * @since 1.4.0
     */
    private function decryptTotpSecret(string $encrypted): string
    {
        if ($encrypted === '') {
            return '';
        }

        $rawKey = env('encryption.key');

        if (empty($rawKey)) {
            log_message('error', 'TOTP decryption failed: encryption.key not configured.');

            return '';
        }

        $key  = hash('sha256', $rawKey, true);
        $data = base64_decode($encrypted, true);

        if ($data === false || strlen($data) < 28) {
            // Minimum: 12 (IV) + 0 (data) + 16 (tag)
            log_message('error', 'TOTP decryption failed: invalid ciphertext format.');

            return '';
        }

        $iv         = substr($data, 0, 12);
        $tag        = substr($data, -16);
        $ciphertext = substr($data, 12, -16);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            log_message('error', 'TOTP decryption failed: authentication tag mismatch or key error.');

            return '';
        }

        return $plaintext;
    }

    /**
     * Create a SHIELD session for a custom user.
     *
     * If the custom user has a shield_user_id, logs them in.
     * Otherwise creates a temporary SHIELD user linked to the custom profile.
     */
    private function createShieldSessionForUser($customUser): void
    {
        if ($customUser->shieldUserId) {
            $shieldUser = auth()->getProvider()->findById($customUser->shieldUserId);

            if ($shieldUser) {
                auth()->login($shieldUser);

                return;
            }
        }

        // Create a SHIELD user linked to custom profile
        $email    = $customUser->email ?? ('fnmt_' . substr($customUser->id, 0, 8) . '@fnmt.local');
        $username = 'fnmt_' . substr($customUser->id, 0, 8);

        $shieldUser = new \CodeIgniter\Shield\Entities\User([
            'email'    => $email,
            'username' => $username,
            'active'   => true,
        ]);

        $provider = auth()->getProvider();
        $provider->save($shieldUser);
        $shieldUser->activate();
        $shieldUser->addGroup('user');

        $this->userModel->linkToShield($customUser->id, $shieldUser->id);

        auth()->login($shieldUser);

        $this->recordEvidence('ShieldSessionCreated', [
            'userId'       => $customUser->id,
            'shieldUserId' => $shieldUser->id,
        ]);
    }

    /**
     * Record an authentication event as evidence.
     *
     * Evidence failures are logged with full context but do not
     * block the primary operation. A separate monitoring process
     * should alert on evidence gaps.
     *
     * @param string $eventType Event type
     * @param array  $payload   Event payload
     * @param string $userAgent Truncated User-Agent
     *
     * @since 1.4.0
     */
    private function recordEvidence(string $eventType, array $payload, string $userAgent = ''): void
    {
        try {
            $this->evidenceModel->createEvidence([
                'eventId'            => \App\Helpers\Uuid::v4(),
                'eventType'          => $eventType,
                'occurredAt'         => date('Y-m-d H:i:s'),
                'aggregateType'      => 'User',
                'aggregateId'        => $payload['userId'] ?? '',
                'actorType'          => 'system',
                'payloadJson'        => json_encode($payload),
                'payloadHash'        => hash('sha256', json_encode($payload)),
                'userAgentTruncated' => $userAgent,
            ]);
        } catch (\Throwable $e) {
            log_message('critical', sprintf(
                'EVIDENCE_LOST: event=%s aggregate=%s error=%s trace=%s',
                $eventType,
                $payload['userId'] ?? 'unknown',
                $e->getMessage(),
                $e->getTraceAsString()
            ));
        }
    }

}
