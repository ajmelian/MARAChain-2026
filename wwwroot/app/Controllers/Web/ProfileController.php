<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Models\DeviceModel;
use App\Models\UserModel;

/**
 * ProfileController — User profile with real SHIELD data.
 *
 * Displays user identity, TOTP status, real SHIELD sessions,
 * and active devices from the database.
 *
 * @package App\Controllers\Web
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.9.0
 */
class ProfileController extends BaseWebController
{
    /**
     * GET /profile — Show authenticated user's profile.
     *
     * @return string Rendered HTML
     */
    public function index(): string
    {
        $shieldUser = auth()->user();

        if ($shieldUser === null) {
            return $this->render('auth/login', [
                'title'   => 'Iniciar sesion',
                'current' => 'login',
            ]);
        }

        // ── Load custom MARAChain user profile ────────────────────
        $userModel  = model(UserModel::class);
        $customUser = $userModel->findByShieldUserId($shieldUser->id ?? 0);

        // ── Load real devices ─────────────────────────────────────
        $deviceModel = model(DeviceModel::class);
        $devices     = [];

        if ($customUser !== null) {
            $devices = $deviceModel->findActiveByUserId($customUser->id);
        }

        // ── Load SHIELD auth identities and sessions ──────────────
        $db          = db_connect();
        $shieldSessions = $db->table('auth_logins')
            ->where('user_id', $shieldUser->id ?? 0)
            ->orderBy('date', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // ── Build profile data ────────────────────────────────────
        $profile = [
            'id'              => $customUser?->id ?? '',
            'identityType'    => $customUser?->identityType ?? 'physical',
            'firstName'       => $customUser?->firstName ?? $shieldUser->username ?? '',
            'lastName'        => $customUser?->lastName ?? null,
            'legalName'       => $customUser?->legalName ?? null,
            'email'           => $shieldUser->email ?? '',
            'emailVerifiedAt' => $customUser?->emailVerifiedAt ?? null,
            'phone'           => $customUser?->phone ?? null,
            'status'          => $customUser?->status ?? 'active',
            'guaranteeLevel'  => $customUser?->guaranteeLevel ?? 'low',
            'totpEnabled'     => (bool) ($customUser?->totpEnabled ?? false),
            'lastLoginAt'     => $customUser?->lastLoginAt ?? null,
            'shieldUserId'    => $shieldUser->id ?? 0,
            'username'        => $shieldUser->username ?? '',
            'shieldSessions'  => $shieldSessions,
            'createdAt'       => $shieldUser->created_at
                ? (is_string($shieldUser->created_at)
                    ? $shieldUser->created_at
                    : $shieldUser->created_at->format('Y-m-d H:i:s'))
                : null,
        ];

        return $this->render('profile/index', [
            'title'    => 'Mi Perfil',
            'current'  => 'profile',
            'user'     => $profile,
            'devices'  => $devices,
        ]);
    }
}
