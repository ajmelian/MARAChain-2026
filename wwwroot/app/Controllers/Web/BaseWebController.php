<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;

/**
 * BaseWebController — base class for web (HTML) controllers.
 *
 * Provides shared view rendering with automatic layout injection
 * and common view data like a simulated authenticated user.
 *
 * @since 1.2.0
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
abstract class BaseWebController extends BaseController
{
    /**
     * Simulated authenticated user for MVP phase (no SHIELD yet).
     *
     * @return array<string, mixed>
     */
    protected function mockUser(): array
    {
        return [
            'id'              => '00000000-0000-4000-a000-000000000001',
            'identityType'    => 'physical',
            'firstName'       => 'Demo',
            'lastName'        => 'Usuario',
            'email'           => 'demo@marachain.local',
            'emailVerifiedAt' => '2026-07-01 10:00:00',
            'phone'           => '+34600000000',
            'status'          => 'active',
            'guaranteeLevel'  => 'high',
            'totpEnabled'     => true,
        ];
    }

    /**
     * Simulated devices for MVP phase.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mockDevices(): array
    {
        return [
            [
                'id'           => 'd1d1d1d1-d1d1-41d1-a1d1-d1d1d1d1d1d1',
                'deviceName'   => 'MacBook Pro Chrome',
                'deviceType'   => 'desktop',
                'firstSeenAt'  => '2026-06-01 09:00:00',
                'lastSeenAt'   => '2026-07-13 08:30:00',
                'status'       => 'active',
            ],
            [
                'id'           => 'd2d2d2d2-d2d2-42d2-a2d2-d2d2d2d2d2d2',
                'deviceName'   => 'iPhone 15 Safari',
                'deviceType'   => 'mobile',
                'firstSeenAt'  => '2026-06-15 14:00:00',
                'lastSeenAt'   => '2026-07-12 19:45:00',
                'status'       => 'active',
            ],
        ];
    }

    /**
     * Render a view with the main layout.
     *
     * @param string               $view View path relative to Views/
     * @param array<string, mixed> $data View data
     *
     * @return string Rendered HTML
     */
    protected function render(string $view, array $data = []): string
    {
        return view($view, $data);
    }
}
