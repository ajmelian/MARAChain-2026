<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Entities\Device;
use App\Entities\User;

/**
 * ProfileController — Controlador web para el perfil de usuario.
 *
 * Muestra la informacion del perfil del usuario autenticado, incluyendo
 * datos personales, nivel de garantia eIDAS, estado TOTP y dispositivos activos.
 *
 * En fase MVP usa los metodos mockUser() y mockDevices() de BaseWebController,
 * convirtiendo los arrays a entidades User y Device para compatibilidad con las vistas.
 *
 * @package App\Controllers\Web
 * @author  Aythami
 * @since   1.2.0
 */
class ProfileController extends BaseWebController
{
    /**
     * GET /profile — Muestra la pagina de perfil del usuario.
     *
     * Renderiza la vista profile/index con los datos del usuario autenticado
     * y sus dispositivos activos.
     *
     * @return string HTML renderizado con el perfil
     *
     * @since 1.2.0
     */
    public function index(): string
    {
        // Obtener datos mock del usuario base y convertir a entidad User
        $mockUserData = $this->mockUser();

        // Anadir campos necesarios para la vista que no estan en mockUser()
        $mockUserData['createdAt']     = '2026-06-01 10:00:00';
        $mockUserData['lastLoginAt']   = '2026-07-13 08:30:00';
        $mockUserData['totpFailures']  = 0;
        $mockUserData['taxIdEncrypted'] = null;

        $user = new User($mockUserData);

        // Obtener datos mock de dispositivos y convertir a entidades Device
        $devices = array_map(
            static fn (array $deviceData): Device => new Device($deviceData),
            $this->mockDevices()
        );

        return $this->render('profile/index', [
            'title'   => 'Mi Perfil',
            'user'    => $user,
            'devices' => $devices,
        ]);
    }
}
