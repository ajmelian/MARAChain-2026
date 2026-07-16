# MARAChain

[![Status](https://img.shields.io/badge/status-In%20Development-yellow)](https://github.com/ajmelian/MARAChain-2026)
[![Version](https://img.shields.io/badge/version-1.8.0-blue)](./VERSION.md)
[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=flat&logo=php)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.x-EE4623?style=flat)](https://codeigniter.com/)
[![SHIELD](https://img.shields.io/badge/SHIELD-1.3-EE4623?style=flat)](https://shield.codeigniter.com/)
[![License](https://img.shields.io/badge/license-GPL%203.0-green)](./LICENSE)
[![SonarQube Quality Gate](https://img.shields.io/badge/Quality_Gate-ERROR_(49.5%25)-red?logo=sonarcloud)](https://sonarcloud.io/dashboard?id=ajmelian_marachain)
[![SonarQube Reliability](https://img.shields.io/badge/Reliability-A_(0_bugs)-brightgreen?logo=sonarcloud)](https://sonarcloud.io/dashboard?id=ajmelian_marachain)
[![SonarQube Security](https://img.shields.io/badge/Security-A_(0_vulns)-brightgreen?logo=sonarcloud)](https://sonarcloud.io/dashboard?id=ajmelian_marachain)
[![SonarQube Maintainability](https://img.shields.io/badge/Maintainability-A-0AA0FF?logo=sonarcloud)](https://sonarcloud.io/dashboard?id=ajmelian_marachain)
[![Coverage](https://img.shields.io/badge/Coverage-42.1%25-yellow?logo=codecov)](https://sonarcloud.io/dashboard?id=ajmelian_marachain)
[![OpenAPI](https://img.shields.io/badge/OpenAPI-3.1-85EA2D?style=flat&logo=openapiinitiative)](./docs/api/marachain-v1.yaml)

> Plataforma de intercambio seguro de documentacion con firma electronica

---

## Descripcion

MARAChain es una plataforma SaaS para la gestion, transmision y custodia segura de documentos entre personas fisicas y, en fases posteriores, entre profesionales, empresas y Administraciones. Combina identidad electronica, autenticacion reforzada, firma electronica delegada, cifrado de extremo a extremo, almacenamiento distribuido privado y un ledger criptografico de evidencias.

### Problema que resuelve

Los canales convencionales presentan limitaciones que MARAChain aborda de forma integrada:

| Limitacion | Solucion MARAChain |
|------------|-------------------|
| El email no garantiza identidad real | Certificado digital FNMT de ciudadano (eIDAS) |
| Una contrasena no prueba quien actua | Autenticacion reforzada: FNMT + TOTP |
| Un enlace puede reenviarse o exponerse | Acceso controlado con sesion Shield + dispositivo |
| El proveedor de almacenamiento puede acceder al contenido | Cifrado E2E en navegador: la DEK nunca sale del cliente |
| Los registros de actividad pueden modificarse | Ledger criptografico append-only con Merkle tree |
| Una descarga no equivale a lectura ni aceptacion | Trazabilidad completa: enviado, accedido, descargado, aceptado |
| Firma, envio, custodia y auditoria en herramientas separadas | Plataforma unificada con todos los flujos integrados |
| La eliminacion entra en conflicto con evidencias historicas | Documentos destruibles sin perder evidencias inmutables |

### Propuesta de valor

| Pilar | Descripcion |
|-------|-------------|
| **Identidad verificada** | Certificado FNMT de ciudadano (nivel eIDAS alto). Futuro: Cl@ve, FirmaProfesional |
| **Autenticacion reforzada** | Certificado FNMT + TOTP → sesion CodeIgniter Shield con permisos por grupo |
| **Cifrado extremo a extremo** | AES-256-GCM via WebCrypto en navegador. La Data Encryption Key nunca abandona el cliente |
| **Firma electronica delegada** | El proveedor de firma recibe exclusivamente el digest. Nunca el documento, DEK, claves privadas ni CID |
| **Ledger criptografico** | Cadena de bloques interna con Merkle tree, append-only, verificable. Preparado para anclaje DLT externo |

### Flujo de alto nivel

```text
Identificacion (FNMT)
    → Autenticacion (TOTP)
    → Cifrado E2E (WebCrypto — AES-256-GCM)
    → Firma (sobre digest del manifiesto)
    → Envio (inbox seguro al destinatario)
    → Evidencias (ledger inmutable con Merkle tree)
```

### Seguridad por diseno

- **Only-4-your-eyes**: sin clave maestra. El proveedor de la plataforma no puede acceder al contenido
- **Zero-knowledge**: el backend almacena exclusivamente ciphertext, nunca plaintext
- **Cifrado en cliente**: la DEK se genera y se destruye en el navegador
- **Append-only**: evidencias y ledger inmutables, sin operaciones de actualizacion ni borrado
- **eIDAS**: niveles de garantia `low` / `substantial` / `high` segun fuente de identidad
- **OWASP Top 10 compliance**: CSRF, XSS, SQLi, cabeceras de seguridad en todas las respuestas

## Autor

**Aythami Melian Perdomo** — Arquitecto de Software Seguro especializado en IAM, autenticacion fuerte y API Security.

| | |
|---|---|
| **Especialidades** | PHP 8.x · OAuth2/OIDC · eIDAS · OWASP |
| **LinkedIn** | [linkedin.com/in/aythami-melian](https://www.linkedin.com/in/aythami-melian/) |
| **Email** | [ajmelper@gmail.com](mailto:ajmelper@gmail.com) |

## Stack Tecnologico

| Capa | Tecnologia | Version |
|------|-----------|---------|
| Backend PHP | CodeIgniter 4 | 4.x |
| Lenguaje | PHP | 8.5 |
| Base de datos | MySQL | 8.x |
| Testing | PHPUnit (SQLite :memory:) | 10.x |
| Static Analysis | PHPStan CI4 | 2.x (dev) |
| Autenticacion | SHIELD | 1.3.x |
| Pagos | Stripe PHP SDK | 21.x |
| Almacenamiento | IPFS privado (cluster) | - |
| API Docs | OpenAPI 3.1 + Swagger UI | - |
| Frontend | Bootstrap 5.3 + PWA + WebCrypto | Latest |
| IaC | VPS remoto via SFTP + systemd workers | - |

## Requisitos Previos

- **PHP** >= 8.2 (recomendado 8.5)
- **Extensiones PHP**: `openssl`, `sodium`, `intl`, `mbstring`, `json`, `curl`, `pdo_mysql`, `fileinfo`, `sqlite3`
- **Composer** >= 2.x
- **MySQL** >= 8.0
- **Git** >= 2.x

## Instalacion Rapida

```bash
# 1. Clonar repositorio
git clone git@github.com:ajmelian/MARAChain-2026.git
cd MARAChain/wwwroot

# 2. Instalar dependencias
composer install

# 3. Configurar entorno
cp env .env
# Editar .env con credenciales de MySQL

# 4. Ejecutar migraciones
php spark migrate

# 5. Configurar autenticacion SHIELD
php spark shield:setup

# 6. Iniciar servidor
php spark serve

# 7. Ejecutar tests
php vendor/bin/phpunit
```

## Estructura del Proyecto

```
MARAChain/
├── docs/                              # Documentos del cliente (baseline)
│   ├── 00_FUENTE_DE_VERDAD.md
│   ├── 01_RESUMEN_COMPLETO.md
│   ├── 02_RESUMEN_EJECUTIVO.md
│   ├── 03_PROYECTO_TECNICO.md
│   ├── 04_ARCHITECTURE.md
│   ├── 05_CASOS_DE_USO.md
│   ├── 06_FRONTEND_DESIGN.md
│   └── 07_NOTIFICATIONS.md
├── docs/                              # Documentos del cliente + API specs
│   ├── api/
│   │   └── marachain-v1.yaml          # OpenAPI 3.1 (46 endpoints, 29 schemas)
│   ├── 00_FUENTE_DE_VERDAD.md
│   ├── 01_RESUMEN_COMPLETO.md
│   ├── 02_RESUMEN_EJECUTIVO.md
│   ├── 03_PROYECTO_TECNICO.md
│   ├── 04_ARCHITECTURE.md
│   ├── 05_CASOS_DE_USO.md
│   ├── 06_FRONTEND_DESIGN.md
│   └── 07_NOTIFICATIONS.md
├── .opencode/openspec/                # Especificacion SDD + Roadmap + State
├── wwwroot/                           # Aplicacion CodeIgniter 4
│   ├── app/
│   │   ├── Commands/                  # 5 comandos CLI
│   │   │   ├── LedgerGenesis.php      # ledger:genesis
│   │   │   ├── LedgerSeal.php         # ledger:seal
│   │   │   ├── NotificationsCommand.php # notifications:send (multi-canal)
│   │   │   ├── TransferExpire.php     # transfers:expire (cron)
│   │   │   └── IpfsReconcile.php      # ipfs:reconcile (IPFS sync)
│   │   ├── Config/                    # Routes, Validation, Filters, SHIELD config
│   │   ├── Controllers/               # 14 REST + 6 Web + Home
│   │   │   └── Api/
│   │   │       └── DocsController.php # Swagger UI (GET /api/docs)
│   │   ├── Database/Migrations/       # 17 migraciones (9 core + 2 auth + 1 linkage + 2 notifications + 2 settings + 1 ipfs)
│   │   ├── Entities/                  # 9 entidades (Entity CI4)
│   │   ├── Filters/                   # SecurityHeaders, Throttle, api-auth
│   │   ├── Helpers/                   # Uuid helper (DRY UUID generation)
│   │   ├── Language/                  # Traducciones (en/Validation)
│   │   ├── Models/                    # 10 modelos
│   │   ├── Notifications/             # Sistema de notificaciones multi-canal
│   │   │   ├── NotificationChannel.php          # Enum PHP (EMAIL, WHATSAPP, TELEGRAM, SMS)
│   │   │   ├── NotificationProviderInterface.php # Contrato send()/health()
│   │   │   ├── NotificationMessage.php          # Value object mensaje
│   │   │   ├── NotificationResult.php           # Value object resultado
│   │   │   ├── RecipientAddress.php             # Value object destinatario
│   │   │   └── Providers/                       # Implementaciones por canal
│   │   │       ├── EmailNotificationProvider.php    # SMTP real
│   │   │       ├── WhatsAppNotificationProvider.php # Stub futuro
│   │   │       ├── TelegramNotificationProvider.php # Stub futuro
│   │   │       └── SmsNotificationProvider.php      # Stub futuro
│   │   ├── Services/                  # 11 servicios (6 impl + 5 interfaces)
│   │   └── Validation/               # CustomRules
│   ├── tests/                         # PHPUnit test suite (33 files, 284 tests, 707 assertions)
│   ├── public/                        # Document root
│   │   ├── manifest.json              # PWA manifest
│   │   ├── sw.js                      # PWA service worker (cache-first)
│   │   └── assets/
│   │       └── plugins/bootstrap/     # Bootstrap 5.3.3 CSS + JS
│   └── writable/                      # Logs, cache, sesiones
├── scripts/
│   ├── deploy-staging.sh              # Deploy script staging
│   ├── deploy-prod.sh                 # Deploy script produccion
│   └── systemd/                       # Systemd service + timer units
│       ├── marachain-notifications.service + .timer
│       ├── marachain-ledger-seal.service + .timer
│       └── marachain-transfers-expire.service + .timer
├── README.md                          # Este fichero
├── ARCHITECTURE.md                    # Decisiones de arquitectura
├── CHANGELOG.md                       # Registro de cambios
├── VERSION.md                         # Politica de versionado
├── CONFIGURATION.md                   # Guia de configuracion
├── SECURITY.md                        # Politica de seguridad
├── INSTALL.md                         # Guia de instalacion
├── AUDITORY.md                        # Trazabilidad de auditorias
├── TODO.md                            # Tareas pendientes
├── nginx-fnmt-mtls.conf               # Nginx mTLS configuration
├── LICENSE                            # GPL-3.0-or-later
└── .gitlab-ci.yml                     # Pipeline CI/CD
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

# Autenticacion SHIELD
php spark shield:setup           # Configurar SHIELD (tablas + config)
php spark shield:user create     # Crear usuario SHIELD

# Comandos MARAChain
php spark ledger:genesis         # Crear bloque genesis del ledger
php spark ledger:seal            # Sellar evidencias en nuevo bloque
php spark notifications:send     # Procesar notificaciones multi-canal pendientes
php spark transfers:expire       # Expirar transferencias caducadas
php spark ipfs:reconcile         # Sincronizar documentos con IPFS privado

# API Documentation
# Swagger UI: http://localhost:8080/api/docs (desarrollo)
# OpenAPI spec: docs/api/marachain-v1.yaml

# CodeIgniter
php spark list                   # Listar comandos disponibles
php spark make:controller        # Generar controlador
php spark make:model             # Generar modelo
php spark make:migration         # Generar migracion
php spark make:entity            # Generar entidad
php spark make:command           # Generar comando CLI

# Calidad de codigo
php vendor/bin/phpunit           # PHPUnit
vendor/bin/phpstan analyse       # Analisis estatico
vendor/bin/php-cs-fixer fix      # Formateo PSR-12
composer audit                   # Auditoria de dependencias
```

## Estado del Proyecto

- **Fase**: MVP (Pre-alpha)
- **Tests**: 284 tests, 707 assertions en 33 archivos (SQLite :memory:)
- **OpenSpec**: 65/66 tareas completadas (Fases 1-7 + Documentacion finalizadas, solo Fase 8 E2E pendiente)
- **Entidades**: 9 implementadas
- **Migraciones**: 17 implementadas (9 core + 2 auth/shield + 1 linkage + 2 notifications + 2 settings + 1 ipfs/blockchain)
- **Modelos**: 10 implementados
- **Controladores REST**: 14 implementados (46+ endpoints, protegidos con api-auth)
- **Controladores Web**: 6 implementados (login, register, inbox, outbox, contacts, profile)
- **Servicios**: 11 (6 implementaciones + 5 interfaces)
- **CLI Commands**: 5 implementados (LedgerGenesis, LedgerSeal, NotificationsCommand, TransferExpire, IpfsReconcile)
- **Validacion**: 9 grupos de reglas + 4 CustomRules
- **Seguridad**: api-auth filter (rutas REST), SecurityHeaders (7 cabeceras OWASP), Rate Limiting (auth + FNMT GET/TOTP), SHIELD auth, Nginx mTLS config
- **Despliegue**: Scripts de deploy staging/prod, .env, nginx-fnmt-mtls.conf, systemd workers (3 services + 3 timers)
- **API Docs**: OpenAPI 3.1 spec (3133 lines, 46 endpoints, 12 tags, 29 schemas) + Swagger UI
- **IPFS**: Cluster privado con reconciliacion automatica (6 tareas IP implementadas)
- **PWA**: manifest.json + service worker (cache-first, 14 static assets)
- **Frontend**: Bootstrap 5.3.3 migrado desde BS4 en todas las vistas
- **CI/CD**: PHPStan level 2, SonarQube integrado, ~~PHP 8.3~~ → PHP 8.5 en CI
- **Auditoria**: 12 correcciones de seguridad aplicadas (v1.2.1), MVP feature audit (v1.4.0), notification system audit (v1.5.0), settings/api-auth audit (v1.6.0), documentation audit (v1.7.0), infra+ipfs+api audit (v1.8.0)

## Documentacion

| Documento | Contenido |
|-----------|-----------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Arquitectura, ADR, diagramas |
| [CHANGELOG.md](./CHANGELOG.md) | Registro de cambios por version |
| [VERSION.md](./VERSION.md) | Politica de versionado semantico |
| [CONFIGURATION.md](./CONFIGURATION.md) | Variables de entorno y configuracion |
| [SECURITY.md](./SECURITY.md) | Politicas y medidas de seguridad |
| [INSTALL.md](./INSTALL.md) | Guia de instalacion paso a paso |
| [AUDITORY.md](./AUDITORY.md) | Trazabilidad de auditorias de codigo |
| [TODO.md](./TODO.md) | Tareas pendientes para version comercial |

## Licencia

GPL-3.0-or-later — ver [LICENSE](./LICENSE) para detalles.
