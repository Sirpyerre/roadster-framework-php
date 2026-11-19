# 🏎️ Roadster Framework

> **Run like a Roadster** - Framework PHP de propósito general para APIs y microservicios

Roadster es un framework PHP de propósito general para APIs y microservicios: scaffolding al estilo Artisan para quien necesita un CRUD rápido, y runtimes de larga vida (RoadRunner hoy, Swoole opcional más adelante) para quien necesita alta concurrencia. Mira el [ROADMAP](ROADMAP.md) para ver hacia dónde va.

---

## ⚡ Quick Start
```bash
# Crear proyecto
composer create-project roadster/framework my-api
cd my-api

# Inicializar
./bin/roadster init

# Iniciar servidor
./bin/roadster serve

# ✅ API corriendo en http://localhost:8080
```

**Prueba el health check:**
```bash
curl http://localhost:8080/health
# {"status":"healthy","timestamp":"2026-01-09T10:30:00+00:00","checks":{"database":"ok"}}
# Si la base de datos no responde: HTTP 503 con "status":"unhealthy" y "checks":{"database":"error"}
```

---

## 🚀 Features

- **🔥 Workers de larga vida**: RoadRunner hoy; Swoole opcional en el [ROADMAP](ROADMAP.md)
- **🛠️ CLI Productivo**: Generadores tipo Laravel Artisan
- **🏗️ Arquitectura Clara**: Domain-driven design by default
- **📦 Sin servicios externos para empezar**: no necesitas base de datos ni otros servicios para arrancar
- **🧩 Capa de datos a elección** (en el roadmap): sin ORM (Doctrine DBAL), Doctrine ORM o Cycle ORM
- **🎯 API-First**: Optimizado para JSON REST APIs
- **🐳 Docker**: la guía incluye un Dockerfile de ejemplo

---

## 📋 Requisitos

- PHP 8.4.1 o superior
- Extensiones: `json`, `mbstring`
- Composer 2.x
- Extensión `pdo` y el driver del motor que uses (`pdo_sqlite` por defecto, `pdo_mysql` o `pdo_pgsql`)
- RoadRunner 2024.x (se instala automáticamente)

---

## 🗄️ Base de datos

Roadster incluye Doctrine DBAL y una conexión lista para usar. Sin configurar nada usa SQLite en `storage/database.sqlite`. Para cambiar de motor, edita `.env`:

```bash
DB_CONNECTION=sqlite          # sqlite (por defecto), mysql o pgsql
DB_DATABASE=storage/database.sqlite   # ruta relativa a la raíz, absoluta o :memory:

# MySQL / PostgreSQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=roadster
# DB_USERNAME=root
# DB_PASSWORD=
```

Inyecta `Doctrine\DBAL\Connection` en tus clases: el contenedor la resuelve sola y la comparte, conectando solo en la primera consulta. Los repositorios generados siguen usando archivos JSON; el driver DBAL llega en el [ROADMAP](ROADMAP.md) (`crud-dbal-driver`).

### Migraciones

Las migraciones usan [Doctrine Migrations](https://www.doctrine-project.org/projects/migrations.html) y viven en `database/migrations/`:

```bash
./bin/roadster make:migration CreateProducts   # Crea database/migrations/Version<fecha>CreateProducts.php
./bin/roadster migrate                         # Ejecuta las migraciones pendientes, en orden
./bin/roadster migrate:rollback                # Revierte la última migración ejecutada
./bin/roadster migrate:status                  # Ejecutadas, disponibles, pendientes y versiones
```

Edita `up()` y `down()` de la clase generada con `$this->addSql('CREATE TABLE ...')`. El historial se guarda en la tabla `migrations`. Si una migración falla no se registra y, en SQLite y PostgreSQL, se revierte todo lo de esa ejecución. MySQL no admite DDL transaccional, así que una migración fallida puede dejar cambios parciales.

---

## 🛠️ Comandos CLI

### Servidor
```bash
./bin/roadster serve              # Inicia RoadRunner
./bin/roadster serve:status       # Indica si el servidor corre (y su pid)
./bin/roadster serve:reload       # Recarga los workers sin detener el servidor
./bin/roadster serve:stop         # Detiene el servidor
```

### Generadores
```bash
./bin/roadster make:handler CreateUser    # Crea handler
./bin/roadster make:service UserService   # Crea service
./bin/roadster make:middleware Auth       # Crea middleware
./bin/roadster make:repository Invoice    # Interfaz, repositorio DBAL, migración y binding
./bin/roadster make:repository Invoice --driver=json   # Con repositorio en archivo JSON
./bin/roadster make:migration CreateInvoices  # Crea una migración de base de datos
./bin/roadster scaffold:crud Product      # CRUD completo (repositorio DBAL por defecto)
./bin/roadster scaffold:crud Product --driver=json     # CRUD con repositorio en archivo JSON
```

### Utilidades
```bash
./bin/roadster routes:list        # Lista todas las rutas
./bin/roadster init               # Inicializa proyecto
```

---

## 📝 Ejemplo Rápido

### 1. Genera un CRUD completo
```bash
./bin/roadster scaffold:crud Task
./bin/roadster migrate          # crea la tabla `tasks`
```

**Esto crea:**
- ✅ 5 handlers (Index, Create, Show, Update, Delete)
- ✅ TaskService (lógica de negocio)
- ✅ TaskRepositoryInterface y DbalTaskRepository (acceso a datos con Doctrine DBAL)
- ✅ Una migración `Version<fecha>CreateTasksTable` en `database/migrations/`
- ✅ Rutas en `config/routes/task.php`
- ✅ Binding del repositorio en `config/dependencies/task.php`

La tabla generada tiene las columnas de la entidad (`id`, `name`, `created_at`, `updated_at`); las fechas se guardan en UTC. Si añades campos, edita a mano la entidad, el repositorio y la migración. Con `--driver=json` se genera `JsonTaskRepository` (archivo en `storage/`) y no hay migración. Los drivers de Doctrine ORM y Cycle ORM llegan más adelante ([ROADMAP](ROADMAP.md)).

### 2. Nada que registrar
Las rutas de `config/routes/*.php` y los bindings de `config/dependencies/*.php` se cargan automáticamente. Las clases con dependencias tipadas en el constructor (handlers, servicios) se resuelven solas (autowiring), así que no hay que tocar `config/container.php` ni `config/routes.php`.

### 3. Implementa la lógica
```php
// src/Domain/Task/TaskService.php
public function create(array $data): Task
{
    // Tu lógica aquí
    return $this->repository->save(new Task($data));
}
```

### 4. ¡Listo!
```bash
curl -X POST http://localhost:8080/api/tasks \
  -H "Content-Type: application/json" \
  -d '{"name":"My first task"}'
```

---

## 📊 Performance

Roadster todavía no publica benchmarks. Las cifras que aparecían antes eran estimaciones sin medir y se retiraron. Un benchmark reproducible (carga, versión de PHP, hardware y comparación entre runtimes) llegará con el cambio `benchmark-harness` del [ROADMAP](ROADMAP.md). Hasta entonces, mide con tu propia carga.

---

## 🏗️ Arquitectura
```
┌─────────────────────────────────────────────┐
│  HTTP Request                               │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  RoadRunner Worker   │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  Middleware Pipeline │
        │  • Error Handler     │
        │  • JSON Response     │
        │  • CORS (opcional)   │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  Router (FastRoute)  │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  Handler             │
        │  ↓                   │
        │  Service (Domain)    │
        │  ↓                   │
        │  Repository          │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  JSON Response       │
        └──────────────────────┘
```

---

## 📁 Estructura del Proyecto
```
my-api/
├── bin/
│   └── roadster              # CLI
├── config/
│   ├── container.php         # Dependency injection (autowiring)
│   ├── database.php          # Parámetros de conexión (desde .env)
│   ├── migrations.php        # Configuración de migraciones
│   ├── dependencies/         # Bindings de interfaces (se cargan solos)
│   ├── routes/               # Rutas por recurso (se cargan solas)
│   └── routes.php            # Rutas principales
├── database/
│   └── migrations/           # Migraciones de base de datos
├── public/
│   └── index.php             # Entry point
├── src/
│   ├── App/                  # Core framework
│   ├── Domain/               # Lógica de negocio
│   ├── Infrastructure/       # Implementaciones
│   └── Web/Rest/             # Handlers (endpoints)
├── tests/
├── .env
├── .rr.yaml                  # Config de RoadRunner
└── composer.json
```

---

## 🐳 Docker

> Ejemplo de Dockerfile: el repositorio todavía no incluye archivos Docker (no hay `Dockerfile` ni `docker-compose.yml`). Adáptalo a tu proyecto.

```dockerfile
FROM php:8.4-cli-alpine

# Instalar RoadRunner
RUN wget https://github.com/roadrunner-server/roadrunner/releases/latest/download/roadrunner-linux-amd64 -O /usr/local/bin/rr \
    && chmod +x /usr/local/bin/rr

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080
CMD ["rr", "serve"]
```
```bash
# Build
docker build -t my-api .

# Run
docker run -p 8080:8080 my-api
```

---

## 🧪 Testing
```bash
# Ejecutar tests
composer test

# Con coverage
composer test:coverage

# Static analysis
composer analyse

# Todo junto
composer check
```

---

## 📚 Documentación

- [Installation Guide](docs/installation.md)
- [Tutorial](docs/tutorial.md)
- [Architecture](docs/architecture.md)
- [CLI Reference](docs/cli.md)
- [Deployment](docs/deployment.md)

---

## 🤝 Comparación con Otros Frameworks

| Feature | Roadster | Laravel | Symfony | Slim |
|---------|----------|---------|---------|------|
| **RoadRunner** | ✅ Nativo | ⚠️ Octane | ⚠️ Manual | ⚠️ Manual |
| **CLI** | ✅ Generadores | ✅ Artisan | ⚠️ Console | ❌ |
| **Learning Curve** | Baja | Media | Alta | Baja |

Comparación cualitativa: no incluye cifras de rendimiento hasta que exista el benchmark.

---

## 💡 ¿Por qué Roadster?

### ✅ Usa Roadster si:
- Quieres **scaffolding rápido** (estilo Artisan) para un CRUD o una API REST
- Construyes **microservicios** que se despliegan en Docker/Kubernetes
- Necesitas **workers de larga vida** y un camino hacia la **alta concurrencia** (RoadRunner hoy, Swoole opcional más adelante)
- Quieres **elegir tu capa de datos**: sin ORM o con Doctrine/Cycle (en el roadmap)
- Priorizas una **arquitectura clara y explícita** (Handler → Service → Repository)

### ❌ NO uses Roadster si:
- Necesitas **vistas HTML** o un frontend integrado (usa Laravel/Symfony)
- Quieres un ecosistema **"todo incluido"** hoy (auth, panel de administración, etc.); varias piezas están en el [ROADMAP](ROADMAP.md)
- Prefieres **convención sobre configuración** extrema

---

## 🗺️ Roadmap

El plan completo vive en [ROADMAP.md](ROADMAP.md): 17 incrementos pequeños, cada uno un cambio de [OpenSpec](openspec/), agrupados en cuatro fases:

- **A. Fundamentos CRUD**: scaffolding con autoregistro, capa de datos (Doctrine DBAL, SQLite por defecto), migraciones y validación
- **B. ORM a elección**: sin ORM, Doctrine ORM o Cycle ORM como drivers opcionales
- **C. Alta concurrencia**: benchmark reproducible, interfaz de runtime y Swoole opcional
- **D. Microservicios**: observabilidad, readiness checks y colas de trabajos

---

## 🌟 Casos de Uso Ideales

- **APIs REST** y backends CRUD
- **Microservicios** en arquitecturas distribuidas
- **Backends** para aplicaciones móviles
- **Procesadores de webhooks**
- **Integraciones B2B** que requieren baja latencia
- **Backends IoT** (ingesta de eventos)

---

## 🤝 Contribuir

¡Las contribuciones son bienvenidas! Por favor lee [CONTRIBUTING.md](CONTRIBUTING.md)

### Proceso:
1. Fork el proyecto
2. Crea tu feature branch (`git checkout -b feature/amazing-feature`)
3. Commit tus cambios (`git commit -m 'feat: add amazing feature'`)
4. Push al branch (`git push origin feature/amazing-feature`)
5. Abre un Pull Request

---

## 💬 Comunidad

- **GitHub Issues**: [Reportar bugs](https://github.com/roadster-php/framework/issues)
- **Discussions**: [Preguntas y ideas](https://github.com/roadster-php/framework/discussions)
- **Discord**: [Únete al chat](https://discord.gg/roadster-php) 🇪🇸 🇬🇧
- **Twitter**: [@roadsterphp](https://twitter.com/roadsterphp)

---

## 📄 Licencia

MIT License - Libre para uso comercial

Copyright (c) 2025 Roadster Framework

---

## 🙏 Agradecimientos

Roadster está construido sobre los hombros de gigantes:

- **[RoadRunner](https://roadrunner.dev)** - Spiral Scout
- **[Mezzio](https://docs.mezzio.dev/)** - Laminas Project
- **[FastRoute](https://github.com/nikic/FastRoute)** - Nikita Popov
- **[Symfony Console](https://symfony.com/doc/current/components/console.html)** - Symfony

---

## 📊 Stats

![GitHub stars](https://img.shields.io/github/stars/roadster-php/framework?style=social)
![GitHub forks](https://img.shields.io/github/forks/roadster-php/framework?style=social)
![GitHub issues](https://img.shields.io/github/issues/roadster-php/framework)
![GitHub license](https://img.shields.io/github/license/roadster-php/framework)
![Packagist Version](https://img.shields.io/packagist/v/roadster/framework)
![Packagist Downloads](https://img.shields.io/packagist/dt/roadster/framework)

---

<p align="center">
  Hecho con ❤️ en México 🇲🇽
  <br>
  <strong>Run like a Roadster</strong>
</p>