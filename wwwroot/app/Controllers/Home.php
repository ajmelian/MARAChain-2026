<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Home controller — Index page.
 *
 * Redirects authenticated users to inbox and guests to login.
 *
 * @package App\Controllers
 * @author  Aythami
 * @since   1.0.0
 */
class Home extends BaseController
{
    /**
     * GET / — Redirecciona segun estado de autenticacion.
     *
     * @return ResponseInterface Redireccion a inbox (logueado) o login (invitado)
     *
     * @since 1.0.0
     */
    public function index(): ResponseInterface
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        return redirect()->route('login');
    }
}
