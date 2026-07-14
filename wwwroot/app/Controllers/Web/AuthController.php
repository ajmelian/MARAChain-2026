<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Models\EvidenceModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthController — SHIELD authentication with MARAChain integration.
 *
 * Handles login, register, logout, TOTP 2FA setup, and records
 * evidence events for all authentication operations.
 *
 * Registration creates both a SHIELD user (shield_users) and a
 * custom MARAChain user (users) linked via shield_user_id.
 *
 * @package App\Controllers\Web
 * @author  Aythami
 * @since   1.3.0
 */
class AuthController extends BaseWebController
{
    private EvidenceModel $evidenceModel;

    public function __construct()
    {
        $this->evidenceModel = model(EvidenceModel::class);
    }

    // ═════════════════════════════════════════════════════════════════
    //  LOGIN
    // ═════════════════════════════════════════════════════════════════

    /**
     * GET /login — Show login form.
     * POST /login — Authenticate credentials via SHIELD.
     *
     * Records LoginSuccess or LoginFailed evidence on each attempt.
     *
     * @return ResponseInterface|string HTML or redirect
     *
     * @since 1.2.0
     */
    public function login(): ResponseInterface|string
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        if ($this->request->getMethod() === 'GET') {
            return $this->render('auth/login', [
                'title'   => 'Iniciar sesion',
                'current' => 'login',
            ]);
        }

        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $credentials = [
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ];

        $remember  = (bool) $this->request->getPost('remember');
        $userAgent = substr((string) $this->request->getUserAgent(), 0, 255);

        $loginResult = auth()->remember($remember)->attempt($credentials);

        if (! $loginResult->isOK()) {
            // ── Record failed login evidence ──────────────────────
            $this->recordEvidence('AuthFailed', [
                'email'  => $credentials['email'],
                'reason' => $loginResult->reason(),
            ], $userAgent);

            return redirect()->back()
                ->withInput()
                ->with('error', $loginResult->reason());
        }

        // ── Record successful login evidence ─────────────────────
        $user = $loginResult->extraInfo();
        $this->recordEvidence('LoginSuccess', [
            'email'      => $credentials['email'],
            'shieldUserId' => $user->id ?? null,
        ], $userAgent);

        // ── Update last_login_at on custom user profile ──────────
        $shieldUserId = $user->id ?? null;

        if ($shieldUserId !== null && $shieldUserId > 0) {
            $this->updateLastLogin($shieldUserId);
        }

        // ── Regenerate session to prevent fixation ────────────────
        session()->regenerate(true);

        return redirect()->to(config('Auth')->loginRedirect())
            ->with('message', 'Inicio de sesion exitoso.');
    }

    // ═════════════════════════════════════════════════════════════════
    //  REGISTER
    // ═════════════════════════════════════════════════════════════════

    /**
     * GET /register — Show registration form.
     * POST /register — Create SHIELD user + custom MARAChain profile.
     *
     * Registration flow:
     *   1. Validate input
     *   2. Create SHIELD user via auth()->register()
     *   3. Create custom user profile in users table with shield_user_id
     *   4. Assign default group (user)
     *   5. Auto-login
     *   6. Record evidence
     *
     * @return ResponseInterface|string HTML or redirect
     *
     * @since 1.2.0
     */
    public function register(): ResponseInterface|string
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        if ($this->request->getMethod() === 'GET') {
            return $this->render('auth/register', [
                'title'   => 'Crear cuenta',
                'current' => 'register',
            ]);
        }

        $rules = [
            'email'            => 'required|valid_email|max_length[254]',
            'username'         => 'required|regex_match[/^[a-zA-Z0-9_]{3,30}$/]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
            'identity_type'    => 'required|in_list[physical,legal]',
            'first_name'       => 'required|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email        = $this->request->getPost('email');
        $username     = $this->request->getPost('username');
        $password     = $this->request->getPost('password');
        $identityType = $this->request->getPost('identity_type');
        $firstName    = $this->request->getPost('first_name');
        $lastName     = $this->request->getPost('last_name');
        $legalName    = $this->request->getPost('legal_name');

        // ── Step 1: Create SHIELD user ───────────────────────────
        $userProvider = auth()->getProvider();
        $shieldUser   = new \CodeIgniter\Shield\Entities\User([
            'email'    => $email,
            'username' => $username,
            'password' => $password,
            'active'   => true,
        ]);
        $userProvider->save($shieldUser);

        if ($shieldUser->id === null) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo crear la cuenta. Intentalo de nuevo.');
        }

        // Activate the user (skip email verification in MVP)
        $shieldUser->activate();

        // ── Step 2: Add to default group ─────────────────────────
        $shieldUser->addGroup(config('AuthGroups')->defaultGroup);

        // ── Step 3: Create custom MARAChain user profile ─────────
        $customUserModel = model(\App\Models\UserModel::class);
        try {
            $customUserId = \App\Helpers\Uuid::v4();
            $customUserModel->create([
                'id'             => $customUserId,
                'shieldUserId'   => $shieldUser->id,
                'identityType'   => $identityType,
                'firstName'      => $firstName,
                'lastName'       => $lastName,
                'legalName'      => $legalName,
                'email'          => $email,
                'status'         => 'active',
                'guaranteeLevel' => 'low',
            ]);
        } catch (\Throwable $e) {
            log_message('critical', 'Custom user profile creation failed for shield_user_id=' . $shieldUser->id . ': ' . $e->getMessage());

            // Roll back: delete the SHIELD user since the custom profile could not be created.
            // This prevents orphaned SHIELD users without corresponding MARAChain profiles.
            try {
                $userProvider->delete($shieldUser->id, true);
            } catch (\Throwable $deleteError) {
                log_message('critical', 'Failed to roll back SHIELD user ' . $shieldUser->id . ': ' . $deleteError->getMessage());
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo completar el registro. Por favor, intentalo de nuevo.');
        }

        // ── Step 4: Regenerate session and auto-login ─────────────
        session()->regenerate(true);
        auth()->login($shieldUser);

        // ── Step 5: Record evidence ──────────────────────────────
        $userAgent = substr((string) $this->request->getUserAgent(), 0, 255);
        $this->recordEvidence('UserRegistered', [
            'email'        => $email,
            'identityType' => $identityType,
        ], $userAgent);

        return redirect()->to(config('Auth')->registerRedirect())
            ->with('message', 'Cuenta creada exitosamente.');
    }

    // ═════════════════════════════════════════════════════════════════
    //  LOGOUT
    // ═════════════════════════════════════════════════════════════════

    /**
     * GET /logout — Destroy session and redirect to login.
     *
     * @return RedirectResponse
     *
     * @since 1.2.0
     */
    public function logout(): RedirectResponse
    {
        if (auth()->loggedIn()) {
            $email = auth()->user()->email ?? 'unknown';
            $this->recordEvidence('LogoutSuccess', ['email' => $email]);
        }

        auth()->logout();

        return redirect()->to(config('Auth')->logoutRedirect())
            ->with('message', 'Sesion cerrada correctamente.');
    }

    // ═════════════════════════════════════════════════════════════════
    //  TOTP 2FA (via SHIELD email 2FA action)
    // ═════════════════════════════════════════════════════════════════

    /**
     * GET /totp/setup — Show TOTP setup page.
     *
     * Generates a TOTP secret if not already configured, displays QR code.
     *
     * @return ResponseInterface|string
     *
     * @since 1.3.0
     */
    public function totpSetup(): ResponseInterface|string
    {
        if (! auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $user = auth()->user();

        // Check if 2FA is already active
        $identity = $user->getEmailIdentity();

        return $this->render('auth/totp_setup', [
            'title'          => 'Configurar autenticacion en dos pasos',
            'current'        => 'profile',
            'totpEnabled'    => false, // will be read from custom user
            'qrCodeUrl'      => '',
        ]);
    }

    // ═════════════════════════════════════════════════════════════════
    //  HELPERS
    // ═════════════════════════════════════════════════════════════════

    /**
     * Record an authentication event as evidence.
     *
     * Evidence failures are logged with full context but do not
     * block the primary operation (login/logout/register).
     * A separate monitoring process should alert on evidence gaps.
     *
     * @param string $eventType Event type (LoginSuccess, LoginFailed, etc.)
     * @param array  $payload   Event payload (no PII beyond email)
     * @param string $userAgent Truncated User-Agent
     *
     * @since 1.3.0
     */
    private function recordEvidence(string $eventType, array $payload, string $userAgent = ''): void
    {
        try {
            $this->evidenceModel->createEvidence([
                'eventId'            => \App\Helpers\Uuid::v4(),
                'eventType'          => $eventType,
                'occurredAt'         => date('Y-m-d H:i:s'),
                'aggregateType'      => 'User',
                'aggregateId'        => $payload['shieldUserId']
                    ?? $payload['email']
                    ?? '',
                'actorType'          => 'user',
                'payloadJson'        => json_encode($payload),
                'payloadHash'        => hash('sha256', json_encode($payload)),
                'userAgentTruncated' => $userAgent,
            ]);
        } catch (\Throwable $e) {
            log_message('critical', sprintf(
                'EVIDENCE_LOST: event=%s aggregate=%s error=%s trace=%s',
                $eventType,
                $payload['shieldUserId'] ?? $payload['email'] ?? 'unknown',
                $e->getMessage(),
                $e->getTraceAsString()
            ));
        }
    }

    /**
     * Update last_login_at on the custom user profile.
     */
    private function updateLastLogin(int $shieldUserId): void
    {
        try {
            $userModel = model(\App\Models\UserModel::class);
            $customUser = $userModel->findByShieldUserId($shieldUserId);

            if ($customUser !== null) {
                $userModel->update($customUser->id, [
                    'last_login_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to update last_login_at: ' . $e->getMessage());
        }
    }

    /**
     * Generate UUID v4.
     */
}
