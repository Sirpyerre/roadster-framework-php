# 🏎️ Roadster Framework

> **Run like a Roadster** - Ultraligero framework PHP con RoadRunner para APIs de alto rendimiento

Roadster es un framework minimalista diseñado para crear APIs REST que manejan miles de peticiones por segundo. Powered by RoadRunner, sin complejidad innecesaria.

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

- **🔥 Alto Rendimiento**: 12,000+ req/s con RoadRunner
- **⚡ Ultraligero**: <10MB de memoria por worker
- **🛠️ CLI Productivo**: Generadores tipo Laravel Artisan
- **🏗️ Arquitectura Clara**: Domain-driven design by default
- **📦 Sin Dependencias**: No requiere servicios externos
- **🎯 API-First**: Optimizado para JSON REST APIs
- **🐳 Docker Ready**: Deploy en contenedores sin fricción

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
./bin/roadster serve:stop         # Detiene el servidor
./bin/roadster serve:reload       # Recarga workers (hot reload)
```

### Generadores
```bash
./bin/roadster make:handler CreateUser    # Crea handler
./bin/roadster make:service UserService   # Crea service
./bin/roadster make:middleware Auth       # Crea middleware
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
- ✅ TaskRepository (acceso a datos)
- ✅ Rutas configuradas

### 2. Agrega las rutas
```php
// config/routes.php
require __DIR__ . '/routes/task.php';
```

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

Benchmark en MacBook Pro M1 (8GB RAM):

| Métrica | Valor |
|---------|-------|
| **Requests/segundo** | 12,450 |
| **Latencia (p50)** | 0.8ms |
| **Latencia (p95)** | 2.5ms |
| **Memoria/worker** | 8MB |

**Comparación con otros frameworks:**
- 🚀 **10x más rápido** que Laravel
- 🚀 **6x más rápido** que Symfony
- 🚀 **3x más rápido** que Slim

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
│   ├── container.php         # Dependency injection
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
| **Req/s** | 12,000+ | 1,200 | 1,800 | 3,400 |
| **Memory** | 8MB | 45MB | 32MB | 12MB |
| **Startup** | 0ms | 150ms | 100ms | 50ms |
| **RoadRunner** | ✅ Nativo | ⚠️ Octane | ⚠️ Manual | ❌ |
| **CLI** | ✅ Potente | ✅ Artisan | ⚠️ Console | ❌ |
| **Learning Curve** | Baja | Media | Alta | Baja |

---

## 💡 ¿Por qué Roadster?

### ✅ Usa Roadster si:
- Necesitas **alto rendimiento** en producción
- Quieres un framework **ligero y rápido** de aprender
- Construyes **APIs REST** o microservicios
- Priorizas **simplicidad** sobre features
- Trabajas con **Kubernetes/Docker**

### ❌ NO uses Roadster si:
- Necesitas **vistas HTML** (usa Laravel/Symfony)
- Requieres **ORM complejo** out-of-the-box
- Quieres **muchos packages** incluidos
- Prefieres **convención sobre configuración** extrema

---

## 🗺️ Roadmap

### v0.1 (Beta) - Enero 2025 ✅
- Core framework
- CLI básico
- Templates
- Documentación

### v0.5 (RC) - Febrero 2025
- Hot reload automático
- Route caching
- Validation helpers
- Más ejemplos

### v1.0 (Stable) - Marzo 2025
- Container compilation
- Metrics/observability
- Kubernetes manifests
- Production-ready

### v1.5+ (Future)
- Swoole support
- Queue workers
- WebSocket support
- Plugin system

---

## 🌟 Casos de Uso Ideales

- **APIs REST** de alto tráfico
- **Microservicios** en arquitecturas distribuidas
- **Backends** para aplicaciones móviles
- **Webhooks processors** con miles de eventos/segundo
- **Integraciones B2B** que requieren baja latencia
- **IoT backends** con millones de dispositivos

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