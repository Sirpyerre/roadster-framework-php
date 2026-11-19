# 🗺️ Roadmap de Roadster

Roadster es un framework PHP de propósito general para APIs y microservicios. Quiere servir a dos públicos a la vez:

- quien necesita un **CRUD básico** rápido, con comandos de scaffolding al estilo Artisan;
- quien necesita **alta concurrencia** en PHP, con runtimes de larga vida.

## Principio

**Un solo código de aplicación, seguro para procesos de larga vida**: servicios sin estado, sin variables globales y con el estado de la petición solo en los atributos PSR-7. Así, cambiar de runtime es configuración, no una reescritura.

## Cómo leer este plan

- Cada fila es un **cambio pequeño e independiente**, gestionado con [OpenSpec](openspec/). Se propone cuando le toca, no antes.
- Las fases agrupan los cambios por objetivo: **A** fundamentos CRUD, **B** ORM a elección, **C** alta concurrencia, **D** microservicios.
- La columna "Depende de" indica el orden mínimo; los cambios sin dependencias pueden hacerse en cualquier momento.
- Este archivo se actualiza cuando se archiva cada cambio. No lleva fechas: el orden lo marcan las dependencias.

## Hecho

- `fix-repo-hygiene`: limpieza de archivos sobrantes y `composer.lock` / `phpunit.xml` versionados.
- `harden-json-repository`: almacenamiento JSON con bloqueo, escritura atómica y errores explícitos (spec `json-file-persistence`).
- `fix-rr-config-symlink`: `.rr.yaml` restaurado como archivo normal, con test de guarda.
- `align-docs-with-vision`: README, `CLAUDE.md`, `composer.json` y contexto de OpenSpec alineados con la visión; este roadmap.
- `fix-serve-stop-reload`: comandos `serve:stop`, `serve:reload` y `serve:status` (spec `server-lifecycle`).
- `container-autoregister`: autowiring del contenedor, `config/dependencies/` y `config/routes/` autocargados, `make:repository` (specs `container-autowiring`, `crud-scaffolding`).
- `database-foundation`: Doctrine DBAL, `config/database.php`, SQLite por defecto y comprobación de base de datos en `/health` (spec `database-connection`).
- `database-migrations`: `make:migration`, `migrate`, `migrate:rollback` y `migrate:status` sobre Doctrine Migrations (spec `database-migrations`).
- `require-php-8-4`: PHP 8.4.1 declarado como versión mínima, acorde con lo que ya exigía `composer.lock`.

## Incrementos

| # | Cambio | Fase | Alcance | Depende de | Estado |
|---|---|---|---|---|---|
| 1 | `fix-rr-config-symlink` | A | Restaurar `.rr.yaml` como archivo normal y añadir un test de guarda | - | hecho |
| 2 | `align-docs-with-vision` | A | Alinear README, `CLAUDE.md`, `composer.json` y el contexto de OpenSpec con esta visión; crear este roadmap | - | hecho |
| 3 | `fix-serve-stop-reload` | A | Comandos `serve:stop`, `serve:reload` y `serve:status` que funcionan (archivo pid y RPC local de RoadRunner) | - | hecho |
| 4 | `container-autoregister` | A | Los generadores registran handlers, servicios y repositorios en el contenedor; `make:repository` | - | hecho |
| 5 | `database-foundation` | A | Doctrine DBAL (sin ORM), `config/database.php`, variables `.env`, SQLite por defecto y comprobación de base de datos en `/health` | - | hecho |
| 6 | `database-migrations` | A | `make:migration`, `migrate`, `migrate:rollback`, `migrate:status` | 5 | hecho |
| 7 | `crud-dbal-driver` | A | `scaffold:crud --driver=dbal` como predeterminado, con migración generada | 4, 5, 6 | planificado |
| 8 | `request-validation` | A | `make:request` y respuestas 422 uniformes | - | planificado |
| 9 | `worker-reset-hook` | B | Servicios "reiniciables" entre peticiones en el bucle del worker | - | planificado |
| 10 | `orm-doctrine` | B | Driver opcional de Doctrine ORM; limpia el `EntityManager` en cada petición | 7, 9 | planificado |
| 11 | `orm-cycle` | B | Driver opcional de Cycle ORM (pensado para workers de RoadRunner) | 7, 9 | planificado |
| 12 | `benchmark-harness` | C | Benchmark reproducible; el README cita sus cifras | 7 | planificado |
| 13 | `runtime-seam` | C | Interfaz `Runtime` con adaptadores RoadRunner y clásico | 9 | planificado |
| 14 | `swoole-driver` | C | Driver opcional de Swoole con pool de conexiones compatible con corrutinas | 12, 13 | planificado |
| 15 | `observability` | D | Monolog conectado, request-id y métricas de RoadRunner | - | planificado |
| 16 | `readiness-checks` | D | Endpoint `/ready` que comprueba la base de datos y dependencias | 5 | planificado |
| 17 | `jobs-queue` | D | Colas con el plugin `jobs` de RoadRunner y `make:job` | 4 | planificado |

## Ideas futuras

- `serve:restart`: detener y volver a arrancar el servidor (como `octane:restart`). Hoy `serve:reload` recarga los workers sin cortar conexiones; `restart` necesita un modo daemon/segundo plano para `serve`, así que se decide cuando exista ese modo.

## Elección de capa de datos

El dominio sigue dependiendo de interfaces de repositorio; la implementación es un detalle de infraestructura:

```
Domain:           TodoRepositoryInterface        (no cambia)
                            ^
Infrastructure:   Json* | Dbal* | DoctrineOrm* | Cycle*

scaffold:crud Product --driver=dbal              (predeterminado)
                      --driver=json | doctrine | cycle
config/database.php:  orm: none | doctrine | cycle
```

Doctrine DBAL viene incluido (camino "sin ORM"). Doctrine ORM y Cycle ORM son opcionales: se instalan con `composer require` y el generador usa el stub del driver elegido.
