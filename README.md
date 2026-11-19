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
# {"status":"healthy","timestamp":"2025-01-09T10:30:00Z"}
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

- PHP 8.3 o superior
- Extensiones: `json`, `mbstring`
- Composer 2.x
- RoadRunner 2024.x (se instala automáticamente)

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
./bin/roadster make:repository Invoice    # Crea interfaz, repositorio JSON y binding
./bin/roadster scaffold:crud Product      # CRUD completo
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
```

**Esto crea:**
- ✅ 5 handlers (Index, Create, Show, Update, Delete)
- ✅ TaskService (lógica de negocio)
- ✅ TaskRepositoryInterface y JsonTaskRepository (acceso a datos)
- ✅ Rutas en `config/routes/task.php`
- ✅ Binding del repositorio en `config/dependencies/task.php`

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
  -d '{"title":"My first task"}'
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
│   ├── dependencies/         # Bindings de interfaces (se cargan solos)
│   ├── routes/               # Rutas por recurso (se cargan solas)
│   └── routes.php            # Rutas principales
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
FROM php:8.3-cli-alpine

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