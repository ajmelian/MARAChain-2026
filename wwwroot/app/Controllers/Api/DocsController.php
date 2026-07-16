<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * DocsController — Swagger UI para la API REST.
 *
 * Sirve la documentacion OpenAPI 3.1 en formato interactivo.
 * Ruta: GET /api/docs
 *
 * @package App\Controllers\Api
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.8.0
 */
class DocsController extends Controller
{
    /**
     * GET /api/docs — Muestra Swagger UI.
     *
     * @return ResponseInterface HTML con Swagger UI
     */
    public function index(): ResponseInterface
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>MARAChain API — Documentacion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body { margin: 0; background: #fafafa; }
        .topbar { display: none; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: '/api.yaml',
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.SwaggerUIStandalonePreset,
            ],
            layout: 'StandaloneLayout',
            defaultModelsExpandDepth: 2,
            defaultModelExpandDepth: 2,
            docExpansion: 'list',
        });
    </script>
</body>
</html>
HTML;

        $this->response->setContentType('text/html; charset=UTF-8');

        return $this->response->setBody($html);
    }
}
