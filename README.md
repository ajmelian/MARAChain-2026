# MARAChain

[![Status](https://img.shields.io/badge/status-In%20Development-yellow)](https://github.com/your-org/marachain)
[![Version](https://img.shields.io/badge/version-1.2.0-blue)](./VERSION.md)
[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=flat&logo=php)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.x-EE4623?style=flat)](https://codeigniter.com/)
[![License](https://img.shields.io/badge/license-MIT-green)](./LICENSE)

> Plataforma de intercambio seguro de documentacion con firma electronica

---

## Descripcion

MARAChain es una plataforma SaaS para gestion, transmision y custodia segura de documentos. Combina identidad electronica, autenticacion reforzada (certificado FNMT + TOTP), firma electronica delegada, cifrado de extremo a extremo (WebCrypto), almacenamiento distribuido privado (IPFS) y un ledger criptografico de evidencias. Modelo _only-4-your-eyes_ sin clave maestra.

## Stack Tecnologico

| Capa | Tecnologia | Version |
|------|-----------|---------|
| Backend PHP | CodeIgniter 4 | 4.x |
| Lenguaje | PHP | 8.5 |
| Base de datos | MySQL | 8.x |
| Testing | PHPUnit (SQLite :memory:) | 10.x |
| Autenticacion | SHIELD (planificado) | - |
| Almacenamiento | IPFS privado | - |
| Frontend | Bootstrap 5 + HTML5 + WebCrypto | Latest |
| IaC | VPS remoto via SFTP | - |

## Requisitos Previos

- **PHP** >= 8.2 (recomendado 8.5)
- **Extensiones PHP**: `openssl`, `sodium`, `intl`, `mbstring`, `json`, `curl`, `pdo_mysql`, `fileinfo`
- **Composer** >= 2.x
- **MySQL** >= 8.0

## Instalacion Rapida

```bash
# 1. Clonar repositorio
git clone git@github.com:your-org/marachain.git
cd marachain/wwwroot

# 2. Instalar dependencias
composer install

# 3. Configurar entorno
cp env .env
# Editar .env con credenciales de MySQL

# 4. Ejecutar migraciones
php spark migrate

# 5. Iniciar servidor
php spark serve

# 6. Ejecutar tests
php vendor/bin/phpunit
```

## Estructura del Proyecto

```
marachain/
├── docs/                              # Documentos del cliente (baseline)
│   ├── 00_FUENTE_DE_VERDAD.md
│   ├── 01_RESUMEN_COMPLETO.md
│   ├── 02_RESUMEN_EJECUTIVO.md
│   ├── 03_PROYECTO_TECNICO.md
│   ├── 04_ARCHITECTURE.md
│   ├── 05_CASOS_DE_USO.md
│   └── 06_FRONTEND_DESIGN.md
├── .opencode/openspec/                # Especificacion SDD + Roadmap
├── wwwroot/                           # Aplicacion CodeIgniter 4
│   ├── app/
│   │   ├── Config/                    # Routes, Validation, Filters, Database
│   │   ├── Controllers/               # 9 controladores REST + BaseController
│   │   ├── Database/Migrations/       # 9 migraciones
│   │   ├── Entities/                  # 9 entidades (Entity CI4)
│   │   ├── Filters/                   # SecurityHeaders
│   │   ├── Models/                    # 9 modelos
│   │   └── Validation/               # CustomRules
│   ├── tests/                         # PHPUnit test suite
│   ├── public/                        # Document root
│   └── writable/                      # Logs, cache, sesiones
├── README.md                          # Este fichero
├── ARCHITECTURE.md                    # Decisiones de arquitectura
├── CHANGELOG.md                       # Registro de cambios
├── VERSION.md                         # Politica de versionado
├── CONFIGURATION.md                   # Guia de configuracion
├── SECURITY.md                        # Politica de seguridad
└── INSTALL.md                         # Guia de instalacion
```

## Comandos Principales

```bash
# Desarrollo
composer test                    # Ejecutar todos los tests
composer run-script test         # Alias
php spark serve                  # Servidor de desarrollo local

# Base de datos
php spark migrate                # Ejecutar migraciones
php spark migrate:rollback       # Revertir ultima migracion
php spark migrate:status         # Estado de migraciones
php spark db:seed DatabaseSeeder # Poblar con datos de prueba

# CodeIgniter
php spark list                   # Listar comandos disponibles
php spark make:controller        # Generar controlador
php spark make:model             # Generar modelo
php spark make:migration         # Generar migracion
php spark make:entity            # Generar entidad

# Calidad de codigo
php vendor/bin/phpunit           # PHPUnit
vendor/bin/phpstan analyse       # Analisis estatico
vendor/bin/php-cs-fixer fix      # Formateo PSR-12
composer audit                   # Auditoria de dependencias
```

## Estado del Proyecto

- **Fase**: MVP (Pre-alpha)
- **Tests**: 164 tests, 390 assertions (SQLite :memory:)
- **Entidades**: 9 implementadas
- **Migraciones**: 9 implementadas
- **Modelos**: 9 implementados
- **Controladores REST**: 9 implementados (24+ endpoints)
- **Validacion**: 9 grupos de reglas
- **Seguridad**: Filtro SecurityHeaders activo (7 cabeceras OWASP)
- **Autenticacion**: SHIELD planificado (pendiente de implementar)

## Documentacion

| Documento | Contenido |
|-----------|-----------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Arquitectura, ADR, diagramas |
| [CHANGELOG.md](./CHANGELOG.md) | Registro de cambios por version |
| [VERSION.md](./VERSION.md) | Politica de versionado semantico |
| [CONFIGURATION.md](./CONFIGURATION.md) | Variables de entorno y configuracion |
| [SECURITY.md](./SECURITY.md) | Politicas y medidas de seguridad |
| [INSTALL.md](./INSTALL.md) | Guia de instalacion paso a paso |

## Licencia

MIT License — ver [LICENSE](./wwwroot/LICENSE) para detalles.
