<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthController — Gestion de autenticacion SHIELD.
 *
 * Proporciona las acciones de login, registro y logout
 * integradas con SHIELD session auth.
 *
 * @package App\Controllers\Web
 * @author  Aythami
 * @since   1.2.0
 */
class AuthController extends BaseWebController
{
    /**
     * GET /login — Muestra el formulario de inicio de sesion.
     *
     * Si el usuario ya esta autenticado, redirige a la bandeja de entrada.
     *
     * @return ResponseInterface|string HTML del formulario o redireccion
     *
     * @since 1.2.0
     */
    public function login(): ResponseInterface|string
    {
        // Si ya esta logueado, redirigir al inbox
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        // GET: mostrar formulario
        if ($this->request->getMethod() === 'GET') {
            return $this->render('auth/login', [
                'title'   => 'Iniciar sesion',
                'current' => 'login',
            ]);
        }

        // POST: procesar autenticacion
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        /** @var array{email: string, password: string} $credentials */
        $credentials = [
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ];

        $remember = (bool) $this->request->getPost('remember');

        $loginResult = auth()->remember($remember)->attempt($credentials);

        if (! $loginResult->isOK()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $loginResult->reason());
        }

        return redirect()->to(config('Auth')->loginRedirect())
            ->with('message', 'Inicio de sesion exitoso.');
    }

    /**
     * GET /register — Muestra el formulario de registro.
     *
     * POST /register — Procesa el registro.
     *
     * @return ResponseInterface|string HTML del formulario o redireccion
     *
     * @since 1.2.0
     */
    public function register(): ResponseInterface|string
    {
        // Si ya esta logueado, redirigir
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        // GET: mostrar formulario
        if ($this->request->getMethod() === 'GET') {
            return $this->render('auth/register', [
                'title'   => 'Crear cuenta',
                'current' => 'register',
            ]);
        }

        // POST: procesar registro
        $rules = [
            'email'            => 'required|valid_email|max_length[254]|is_unique[auth_identities.secret]',
            'username'         => 'required|regex_match[/^[a-zA-Z0-9_]{3,30}$/]|is_unique[shield_users.username]',
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

        $email       = $this->request->getPost('email');
        $username    = $this->request->getPost('username');
        $password    = $this->request->getPost('password');
        $identityType = $this->request->getPost('identity_type');
        $firstName   = $this->request->getPost('first_name');
        $lastName    = $this->request->getPost('last_name');
        $legalName   = $this->request->getPost('legal_name');

        // Registrar usuario via SHIELD
        $user = auth()->register([
            'email'    => $email,
            'username' => $username,
            'password' => $password,
        ]);

        if ($user === null) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo crear la cuenta. Intentalo de nuevo.');
        }

        // Agregar al grupo por defecto (user)
        model('CodeIgniter\Shield\Models\UserModel')->addToDefaultGroup($user);

        // Almacenar datos adicionales en el perfil personalizado
        // (la tabla users del proyecto se usa para datos adicionales)
        $customUserModel = model(\App\Models\UserModel::class);
        try {
            $customUserModel->create([
                'id'            => (string) $user->id,
                'identity_type' => $identityType,
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'legal_name'    => $legalName,
                'email'         => $email,
                'status'        => 'active',
            ]);
        } catch (\RuntimeException $e) {
            // Si falla la creacion del perfil, continuamos de todas formas
            log_message('error', 'Failed to create custom user profile: ' . $e->getMessage());
        }

        // Login automatico post-registro
        auth()->login($user);

        return redirect()->to(config('Auth')->registerRedirect())
            ->with('message', 'Cuenta creada exitosamente.');
    }

    /**
     * GET /logout — Cierra la sesion del usuario.
     *
     * @return RedirectResponse Redirige a la pagina de login
     *
     * @since 1.2.0
     */
    public function logout(): RedirectResponse
    {
        auth()->logout();

        return redirect()->to(config('Auth')->logoutRedirect())
            ->with('message', 'Sesion cerrada correctamente.');
    }

}
