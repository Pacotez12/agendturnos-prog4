# AgendTurnos — Documento general del proyecto

Sistema web de agendamiento de turnos odontológicos para **Glow Dental Protect**, Asunción, Paraguay.

> Documento vivo: se actualiza a medida que avanza el desarrollo. Cada sección indica su estado: ✅ hecho · 🚧 en curso · ⏳ pendiente.
> Última actualización: 2026-10-19.
> Diseño de interfaz (colores, layouts y componentes): [`docs/diseno-ui.md`](diseno-ui.md).

---

## 1. Contexto y problema

Glow Dental Protect es una clínica odontológica unipersonal. Ofrece consultas, limpiezas, empastes, extracciones y tratamientos de conducto, además de ortodoncia, blanqueamiento, prótesis e implantes.

**Situación actual.** Los turnos se piden por teléfono, WhatsApp, Instagram y Facebook, y se anotan a mano en planillas de Excel. La propia profesional hace este trabajo, porque no hay personal de recepción. Esto le quita tiempo de atención, provoca errores de agenda y genera demoras para los pacientes.

**Solución.** Una plataforma web donde:
- el paciente consulta los servicios, ve los horarios libres, pide un turno y sigue su estado;
- la profesional administra su agenda y su disponibilidad, y confirma o modifica turnos;
- el sistema envía confirmaciones y recordatorios por correo.

## 2. Alcance

### Incluido
- Portal web con información de la clínica y catálogo de servicios.
- Registro e inicio de sesión, con roles **paciente**, **profesional** y **funcionario** (recepción/administración).
- Administración de especialidades, servicios y qué servicios ofrece cada profesional.
- Consulta de disponibilidad y solicitud de turnos.
- Agenda de la profesional: ver, confirmar, reprogramar y cancelar turnos; administrar el horario semanal y los bloqueos.
- Seguimiento del estado del turno por parte del paciente.
- Notificaciones (confirmación, cancelación, reprogramación) y recordatorios automáticos.

### Fuera de alcance (versión inicial)
Historia clínica, facturación y contabilidad, inventario, gestión de varias sucursales (el modelo de datos queda preparado), app móvil nativa, integración directa con redes sociales, pagos en línea, telemedicina, IA y marketing.

## 3. Stack tecnológico

| Capa | Tecnología | Estado |
|---|---|---|
| Framework | Laravel 13 (PHP 8.4) | ✅ |
| Base de datos | PostgreSQL 16 | ✅ |
| Sesiones, caché y colas | Redis 7 (`phpredis`) | ✅ |
| Vistas | Blade + Blade Components | ✅ |
| Estilos | Tailwind CSS 4 (compilado con Vite) | ✅ |
| Interactividad | Alpine.js | ✅ |
| Servidor web, arquitectura A | Nginx + PHP-FPM | ✅ |
| Servidor web, arquitectura B | Apache + mod_php | ✅ |
| Balanceador | HAProxy (stats en puerto 8404) | ✅ |
| Correo en desarrollo | Mailpit | ✅ |
| Contenedores | Docker / Docker Compose | ✅ |
| Orquestación y despliegue | Portainer (Stack) | ⏳ |
| Gestión del proyecto | Scrum en Jira | ✅ |

## 4. Arquitectura

**Estilo de software: monolito MVC en capas (N-Tier).** Una sola aplicación Laravel, organizada en capas horizontales:

| Capa | Componentes | Responsabilidad |
|---|---|---|
| Presentación | Rutas, controladores, FormRequests, vistas Blade y Blade Components | Recibir la petición, validar la entrada y devolver la vista |
| Negocio | Servicios en `app/Services` (ej. `AgendaService`, `DisponibilidadService`) | Reglas del dominio: horarios libres, solicitud y cambios de estado de turnos |
| Datos | Modelos Eloquent (relaciones, scopes, casts) y PostgreSQL | Persistencia e integridad |

Flujo de una petición: ruta → FormRequest → Controller → Service → Model → PostgreSQL → vista Blade. Los controladores se mantienen delgados; no se usa una capa de repositorios porque Eloquent ya cumple ese rol.

### 4.1 Entorno de desarrollo actual ✅

Ocho contenedores en la red interna `agendturnos_default`. Entre ellos se comunican por el nombre del servicio.

```mermaid
flowchart LR
    B[Navegador] -- "localhost:8080" --> W[web<br/>Nginx]
    W -- "FastCGI app:9000" --> A[app<br/>PHP-FPM + Laravel]
    B -- "localhost:8081" --> AP[apache<br/>Apache + mod_php + Laravel]
    AP --> D
    AP --> R
    A -- "db:5432" --> D[(db<br/>PostgreSQL 16)]
    A -- "redis:6379" --> R[(redis<br/>Redis 7)]
    A -- "SMTP mailpit:1025" --> M[mailpit]
    B -- "localhost:8025" --> M
    N[node<br/>Vite] -- "public/build" --> W
```

| Servicio | Imagen | Puerto interno | Puerto en el host | Función |
|---|---|---|---|---|
| `web` | `nginx:alpine` | 80 | **8080** | Sirve los estáticos y pasa los `.php` a `app` |
| `app` | `php:8.4-fpm-alpine` | 9000 | — | Ejecuta Laravel |
| `haproxy` | `haproxy:3.0-alpine` | 80 / 8404 | **8000** / **8404** | Balancea entre `web` (A) y `apache` (B); Stats en el 8404 (ver 4.2) |
| `apache` | `php:8.4-apache` | 80 | **8081** | Arquitectura B: Apache + mod_php con el mismo código, la misma base y el mismo Redis que A |
| `db` | `postgres:16-alpine` | 5432 | **5436** | Base `agendturnos` |
| `redis` | `redis:7-alpine` | 6379 | **6379** | Sesiones, caché y colas |
| `mailpit` | `axllent/mailpit` | 1025 / 8025 | **8025** | Atrapa los correos de desarrollo |
| `node` | `node:24-alpine` | — | — | Compila Tailwind y Alpine.js con Vite (`npm run build -- --watch`) hacia `public/build` |

**Recorrido de una petición:** el navegador llama a `web:80`. Nginx sirve el archivo si existe en `public/`; si no, deriva a `index.php`, que va a `app:9000` por FastCGI. Laravel consulta `db` y lee o escribe la sesión en `redis`.

**Detalles:**
- El código se monta con `.:/var/www/html:z`; el sufijo `:z` es para SELinux. En `web` el montaje es de solo lectura.
- Los datos persisten en los volúmenes `pgdata` y `redisdata`.
- El usuario `www-data` del contenedor usa el UID 1000, igual que el del host.
- `app` espera a que `db` y `redis` pasen sus healthchecks antes de arrancar.

### 4.2 Balanceo con HAProxy ✅

```mermaid
flowchart LR
    U[Usuarios] -- "localhost:8000" --> H[HAProxy<br/>:80 · stats :8404]
    H -- "roundrobin + health check /health" --> A1[Arquitectura A<br/>Nginx + PHP-FPM]
    H -- "roundrobin + health check /health" --> B1[Arquitectura B<br/>Apache + mod_php]
    A1 --> D[(PostgreSQL)]
    B1 --> D
    A1 --> R[(Redis<br/>sesiones compartidas)]
    B1 --> R
```

- Las dos arquitecturas corren el mismo código y comparten la base de datos y Redis.
- Como la sesión vive en Redis, el usuario no pierde la sesión cuando HAProxy lo cambia de nodo.
- **Entrada:** http://localhost:8000 (HAProxy). Las arquitecturas siguen accesibles directo en 8080 (A) y 8081 (B) para compararlas. Stats en http://localhost:8404. La cabecera `X-Servidor` de cada respuesta dice qué nodo la atendió (`a_nginx` o `b_apache`).
- **Algoritmo `roundrobin`:** las peticiones son cortas y parecidas y la sesión vive en Redis, así que no hace falta fijar al usuario a un nodo. `leastconn` conviene con conexiones largas (websockets, descargas), que esta app no tiene.
- **Health check:** `GET /health` cada 3 s con 5 s de timeout (en Windows, con el código montado, Laravel tarda ~2 s). `/health` responde 200 solo si el nodo llega a PostgreSQL y a Redis. 3 fallos seguidos → DOWN; 2 aciertos → UP.
- **Failover:** si un nodo deja de responder, HAProxy lo marca DOWN y manda todo el tráfico al otro. Cuando el nodo vuelve, pasa a UP solo. Mientras detecta la caída, `option redispatch` reintenta en el otro nodo las conexiones fallidas, así que el usuario no ve errores. HAProxy re-resuelve los nombres con el DNS de Docker: si el contenedor vuelve con otra IP, la toma.
- **Benchmarking:** se mide RPS y latencia (p50, p95 y p99) de A, de B y del conjunto balanceado.

## 5. Modelo de datos ✅

Migraciones, seeders y tests de integridad (SCRUM-25); modelos Eloquent con relaciones, Enums (`RolUsuario`, `EstadoTurno`, `EstadoNotificacion`) y factories (SCRUM-40). Pendiente: el detalle en `docs/der.md`, `docs/diccionario-datos.md` y `docs/normalizacion.md`.

```mermaid
erDiagram
    users ||--o| pacientes : "cuenta de"
    users ||--o{ pacientes : "responsable de"
    users ||--o| profesionales : "cuenta de"
    especialidades ||--o{ servicios : "agrupa"
    profesionales ||--o{ profesional_servicio : "ofrece"
    servicios ||--o{ profesional_servicio : "ofrecido por"
    profesionales ||--o{ disponibilidad : "define"
    consultorios ||--o{ disponibilidad : "en"
    profesionales ||--o{ bloqueos : "registra"
    consultorios |o--o{ bloqueos : "en"
    pacientes ||--o{ turnos : "tiene"
    profesionales ||--o{ turnos : "atiende"
    servicios ||--o{ turnos : "se reserva en"
    consultorios ||--o{ turnos : "en"
    turnos ||--o{ historial_turnos : "cambios de estado"
    users ||--o{ notificaciones : "recibe"
    turnos |o--o{ notificaciones : "referencia"
```

| Tabla | Contenido | Restricciones |
|---|---|---|
| `users` | Login: `email`, `password`, `rol` (`paciente`, `profesional`, `funcionario`) | `email` único; `CHECK` de rol |
| `pacientes` | `nombre`, `apellido`, `tipo_documento`, `documento`, `telefono`, `fecha_nacimiento`, `user_id` (nullable), `responsable_id` (nullable) | Único `(tipo_documento, documento)`; `user_id` único |
| `profesionales` | `nombre`, `apellido`, `matricula`, `user_id` | `user_id` único |
| `especialidades` | `nombre`, `activa` | `nombre` único |
| `servicios` | `nombre`, `descripcion`, `duracion_min`, `precio` (entero, guaraníes), `activo`, `especialidad_id` | `duracion_min > 0`; `precio >= 0` |
| `profesional_servicio` | Qué servicios ofrece cada profesional | Clave compuesta `(profesional_id, servicio_id)` |
| `consultorios` | `nombre`, `direccion`, `telefono`, `activo` | Una fila hoy; escalable a sucursales |
| `disponibilidad` | Franjas semanales: `dia_semana`, `hora_inicio`, `hora_fin`, profesional y consultorio | `dia_semana` 1–7 (ISO, 1 = lunes); `hora_fin > hora_inicio` |
| `bloqueos` | Ausencias puntuales: `inicio`, `fin`, `motivo`, profesional, consultorio opcional | `fin > inicio`; sin consultorio = no atiende en ningún lugar |
| `turnos` | `inicio`, `fin` (`timestamptz`), `estado`, `observaciones`, `motivo_cancelacion`, paciente, profesional, servicio, consultorio | Ver reglas abajo |
| `historial_turnos` | `estado_anterior`, `estado_nuevo`, usuario que hizo el cambio (nulo en cambios automáticos), fecha | — |
| `notificaciones` | `tipo`, `canal`, `estado`, `enviado_at`, destinatario, turno opcional | Ver reglas abajo |

**Pacientes y cuentas.** Un paciente puede no tener cuenta propia: un menor se asocia a un responsable (`responsable_id`), y recepción puede registrar a quien pide turno por teléfono. Por eso los turnos referencian a `pacientes` y no a `users`.

**Reglas de integridad clave:**
- **Sin solapamiento:** `EXCLUDE USING gist (profesional_id WITH =, tstzrange(inicio, fin, '[)') WITH &&) WHERE (estado <> 'cancelado')`. Requiere `btree_gist`. El rango `[)` permite turnos contiguos (10:00–10:30 y 10:30–11:00).
- **El profesional ofrece el servicio:** FK compuesta `turnos(profesional_id, servicio_id)` → `profesional_servicio`.
- **Estados válidos:** `CHECK` en la base más Enums de PHP.
  - Turno: `solicitado`, `confirmado`, `atendido`, `cancelado`, `ausente`.
  - Notificación: `pendiente`, `enviada`, `fallida`.
- **Sin notificaciones duplicadas:** índice único parcial `(turno_id, tipo, destinatario_id) WHERE turno_id IS NOT NULL`.
- **Duración:** `servicios.duracion_min` es el valor por defecto; el turno guarda su propio `fin` y puede ajustarse puntualmente.

**Índices:** `turnos (profesional_id, inicio)` para la agenda; `turnos (paciente_id, inicio)` para el historial del paciente; `notificaciones (estado)` para la cola de envío.

## 6. Módulos y rutas

| Módulo | Historias | Rutas previstas | Estado |
|---|---|---|---|
| Autenticación y usuarios | HU-01, HU-02 | `/registro`, `/login`, `/logout`, `/inicio` (paciente), `/panel` | ✅ |
| Servicios y especialidades | HU-03, SCRUM-39 | `/servicios` (catálogo público) · `/panel/servicios`, `/panel/especialidades`, `/panel/profesionales/{id}/servicios` | ✅ |
| Agendamiento | HU-04, HU-05 | `/turnos/nuevo` y la consulta de horarios libres | ⏳ |
| Agenda de la profesional | HU-06, HU-07, HU-11 ✅, HU-12 | `/agenda`, `/agenda/turnos/{id}`, `/panel/disponibilidad` | 🚧 |
| Seguimiento | HU-08 | `/mis-turnos` | ⏳ |
| Notificaciones | HU-09, HU-10 | — (colas y scheduler) | ⏳ |
| Salud | — | `/health` (de Laravel; verifica PostgreSQL y Redis; health check de HAProxy) | ✅ |

**Transiciones de estado de un turno:**

```mermaid
stateDiagram-v2
    [*] --> solicitado
    solicitado --> confirmado
    solicitado --> cancelado
    confirmado --> atendido
    confirmado --> cancelado
    confirmado --> ausente
```

## 7. Decisiones técnicas

| Decisión | Motivo |
|---|---|
| PostgreSQL en vez de MySQL | Restricciones de exclusión con rangos de tiempo: el solapamiento de turnos se impide en la propia base, no solo en la aplicación. |
| Sesiones en Redis | Requisito para balancear entre nodos sin perder la sesión del usuario. |
| `inicio` y `fin` en lugar de `fecha` + `hora` | Sin hora de fin no hay forma de detectar solapamientos. `fin` se guarda porque es la duración pactada al reservar, aunque el servicio cambie después. |
| `profesionales` y `pacientes` como tablas aparte de `users` | `users` queda solo para el login. Permite pacientes sin cuenta (menores, alta por teléfono) y varios profesionales. |
| `consultorios` como tabla propia | Hoy hay uno solo, pero la disponibilidad y los turnos ya lo referencian: sumar una sucursal no requiere migrar datos. |
| FK compuesta hacia `profesional_servicio` | La base impide reservar un servicio que el profesional no ofrece. |
| Monolito MVC en capas, sin repositorios | Un solo proyecto, menos piezas que mantener. La lógica de negocio vive en servicios; Eloquent es la capa de datos. |
| Envío de correos por cola | Mandar un correo no debe demorar la respuesta al usuario. |
| Blade + Tailwind + Alpine.js, sin SPA | El frontend lo genera Laravel en el servidor (la "V" del MVC). Alpine.js agrega interacción (menús, modales, selección de horarios) escribiendo atributos dentro del mismo HTML, sin un proyecto ni una API aparte. |
| Horarios libres calculados en `DisponibilidadService` | Una sola lógica para el paciente, el operador y la vista previa: inicios cada 15 minutos (configurable), el servicio entra entero en la franja, sin chocar con turnos no cancelados ni bloqueos, y nunca en el pasado. |
| Conexión a PostgreSQL en `America/Asuncion` | Las fechas sin zona que envía Laravel se interpretan en hora de Asunción, no en UTC del contenedor. |
| Mailpit en desarrollo | Permite probar correos sin enviar nada real. |

## 8. Despliegue

### 8.1 Local ✅

```bash
cp .env.example .env
make build && make up
make artisan cmd=key:generate
make migrate
```

| Atajo | Acción |
|---|---|
| `make up` / `make down` | Levantar / apagar |
| `make build` | Reconstruir las imágenes |
| `make sh` | Shell dentro de `app` |
| `make artisan cmd=...` | Comando `php artisan` |
| `make migrate` / `make fresh` | Migrar / reconstruir la base |
| `make logs` | Ver logs en vivo |

### 8.2 Portainer (Stack) ⏳

El compose actual es de desarrollo: monta el código desde el disco y construye las imágenes al levantar. Para desplegarlo como Stack de Portainer hace falta:
- una imagen de la app con el código copiado dentro (`COPY` + `composer install --no-dev`);
- un compose de despliegue sin montajes de código, que después incorpore HAProxy y la arquitectura B;
- no exponer Redis ni PostgreSQL fuera de la red interna.

## 9. Gestión del proyecto

Scrum con sprints de 2 semanas en Jira (proyecto `SCRUM`).

| Sprint | Fechas | Objetivo |
|---|---|---|
| Sprint 1 | 2–16/10 | Entorno, modelo de datos, autenticación, servicios, disponibilidad y solicitud de turnos; sesiones en Redis; Portainer |
| Sprint 2 | 16–30/10 | Agenda de la profesional, seguimiento, arquitectura B, HAProxy y failover |
| Sprint 3 | 30/10–13/11 | Notificaciones y recordatorios; scripts de benchmark |
| Sprint 4 | 13–27/11 | Benchmark A vs B, Stats con tráfico real, documentación final |

| Rol | Responsable | Tickets |
|---|---|---|
| Scrum Master y full stack | Marco Brizuela | Ceremonias y documentación; layout base y componentes UI (SCRUM-38); login y roles (HU-02); catálogo y CRUD de servicios (HU-03); especialidades y servicios por profesional (SCRUM-39); disponibilidad horaria (HU-11) |
| Backend | Juan Morán | Modelo de datos (SCRUM-25); solicitar turno (HU-05); confirmar, reprogramar o cancelar (HU-07); notificaciones y recordatorios (HU-09, HU-10) |
| DevOps | Julio Ybañez | Sesiones en Redis, Portainer, arquitectura B, HAProxy, demo de failover |
| Frontend | Enzo Ferrari | Registro (HU-01), horarios disponibles (HU-04), agenda (HU-06), estado de mis turnos (HU-08), detalle del turno (HU-12) |
| QA | Félix Villasanti | Plan y casos de prueba, scripts de benchmark, benchmark A vs B |

Cada historia la implementa de punta a punta (backend y vista) quien la tiene asignada.

## 10. Convenciones

- **Ramas:** `feat/SCRUM-<n>-<descripcion>` (ej. `feat/SCRUM-25-modelo-datos`) y Pull Request a `main`, con revisión de un compañero.
- **Commits:** prefijos convencionales (`feat:`, `fix:`, `chore:`, `docs:`, `test:`).
- **Idioma:** dominio en español (tablas, columnas, rutas); código de framework en su forma original.
- **Hecho:** criterios de aceptación cumplidos, tests en verde, QA aprobado y ticket en Finalizado.
- **Secretos:** `.env` nunca se commitea; `.env.example` lleva solo valores de desarrollo.

## 11. Registro de cambios

| Fecha | Cambio |
|---|---|
| 2026-10-19 | HAProxy (SCRUM-30): balanceo `roundrobin` entre A y B en el puerto 8000, health check contra `/health` (antes `/up`; ahora verifica PostgreSQL y Redis), `option redispatch`, Stats en el 8404 y cabecera `X-Servidor`. Incluido en el Stack de Portainer. |
| 2026-10-19 | Arquitectura B (SCRUM-29): servicio `apache` (Apache + mod_php, PHP 8.4 con las mismas extensiones y `php.ini` que A) en el puerto 8081. Comparte código, base y Redis con A, así que la sesión iniciada en un servidor sirve en el otro. También en el Stack de Portainer. |
| 2026-10-02 | Repositorio creado. Entorno Docker base: Laravel 13, PostgreSQL 16, Redis 7, Nginx + PHP-FPM y Mailpit (`cefc8f7`). |
| 2026-10-02 | Diseño del modelo de datos corregido (SCRUM-25). |
| 2026-10-09 | Creación de este documento. |
| 2026-10-13 | Modelo de datos actualizado: pacientes, especialidades, `profesional_servicio`, consultorios, responsable de menores, FK compuesta y reglas de integridad. Estilo de arquitectura documentado (monolito MVC en capas). |
| 2026-10-18 | Estándar de diseño de interfaz aprobado (`docs/diseno-ui.md`): marca de la clínica, estilo cálido para el paciente y clínico para el panel, agenda por profesional, agendamiento por operador (HU-13) y familiares a cargo. |
| 2026-10-18 | Modelos Eloquent, Enums y factories para todas las tablas (SCRUM-40). |
| 2026-10-19 | Página de inicio pública (`/`) con la información de la clínica y sus profesionales; íconos Phosphor (`codeat3/blade-phosphor-icons`) y regla de no usar emojis en la interfaz (verificada por un test); integración continua con GitHub Actions; matrículas reales en los datos de ejemplo. |
| 2026-10-19 | Recuperación de contraseña por correo (enlace de 60 minutos, sin revelar qué cuentas existen), correos en español con la marca, `make portainer` y número de WhatsApp de la clínica. |
| 2026-10-19 | Especialidades y servicios por profesional (SCRUM-39): alta, edición y desactivación de especialidades; asignación de servicios a cada profesional, conservando los que tienen turnos y los inactivos. |
| 2026-10-18 | Disponibilidad horaria (SCRUM-18): horario semanal por profesional, bloqueos con aviso de turnos afectados y vista previa. `DisponibilidadService` (`franjasLibres`, `estaLibre`, `consultorioPara`) con inicios cada `AGENDA_INTERVALO_MINUTOS` (15). La conexión a PostgreSQL usa la zona `America/Asuncion`. |
| 2026-10-18 | Catálogo público de servicios y administración para recepción (SCRUM-13); columna `descripcion` en `servicios`; datos de ejemplo con las dos profesionales reales y sus especialidades. Corrección: las fuentes de la marca se cargan con `@fonts` en los layouts. |
| 2026-10-18 | Inicio de sesión y acceso por rol (SCRUM-12): login con límite de 5 intentos por minuto, redirección según el rol, middleware `rol:` y páginas de inicio del paciente y del panel. |
| 2026-10-18 | Guía para hacer pantallas (`docs/guia-frontend.md`) con capturas, plantilla de PR con checklist de UI y `AGENTS.md` con las reglas del proyecto para asistentes de código. |
| 2026-10-18 | Layout base, componentes Blade y diseño UI implementados (SCRUM-38): tokens de marca, fuentes, Alpine.js, logo, layouts invitado/paciente/panel y página de referencia /ui. |
| 2026-10-18 | Registro de pacientes (SCRUM-11). Contenedor `node` que compila los assets con Vite, sin Node en la máquina. |
| 2026-10-16 | Modelo de datos implementado: 15 migraciones con sus restricciones, seeders y 11 tests de integridad en PostgreSQL (SCRUM-25, `77a3a45`). |
| 2026-10-13 | Stack de frontend definido: Blade + Tailwind CSS 4 + Alpine.js (Vite). |
| 2026-10-13 | Reparto de tickets por bloques (configuración del sistema / ciclo del turno); nuevas tareas SCRUM-38 (layout) y SCRUM-39 (especialidades). Convención de ramas `feat/SCRUM-<n>-...`. |
