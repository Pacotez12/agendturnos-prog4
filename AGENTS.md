# AGENTS.md

Contexto para asistentes de código (Claude Code, Gemini CLI, Cursor, Codex, etc.) que trabajen en este repositorio.

## Proyecto

Sistema web de agendamiento de turnos para Glow Dental Protect, clínica odontológica de Asunción con dos profesionales. Pacientes reservan desde la web; profesionales y recepción gestionan la agenda desde un panel.

Documentación que hay que leer antes de cambiar algo:
- `docs/arquitectura.md`: documento general (stack, contenedores, modelo de datos, decisiones). Se actualiza en cada cambio relevante, incluida una fila en su "Registro de cambios".
- `docs/diseno-ui.md`: estándar de interfaz (colores, layouts, componentes, accesibilidad).
- `docs/guia-frontend.md`: cómo construir una pantalla, con ejemplos.

## Stack y arquitectura

- Laravel 13, PHP 8.4. Monolito MVC en capas: ruta → FormRequest → Controller → Service (`app/Services`) → Model Eloquent → PostgreSQL → vista Blade. Controladores delgados; la lógica de negocio va en servicios. No se usa capa de repositorios ni view models.
- PostgreSQL 16: restricciones en la base (exclusion constraint de turnos sin solapamiento, FK compuesta a `profesional_servicio`, CHECKs). No reemplazarlas por validación solo en PHP.
- Redis 7 para sesión, caché y colas.
- Frontend: Blade + Blade Components + Tailwind CSS 4 + Alpine.js, compilados con Vite. Sin SPA, sin API separada.
- Enums en `app/Enums` (`RolUsuario`, `EstadoTurno`, `EstadoNotificacion`); usarlos en lugar de strings sueltos.
- Dominio en español (tablas, columnas, rutas, clases del dominio); código del framework en su forma original.

## Entorno y comandos

Todo corre en Docker; no hace falta PHP ni Node en la máquina.

```bash
docker compose up -d                                      # entorno completo (incluye node, que compila los assets)
docker compose exec -T -u 1000 app php artisan test       # tests (PostgreSQL, base agendturnos_test)
docker compose exec -T -u 1000 app ./vendor/bin/pint      # estilo de código
docker compose exec -T -u 1000 app php artisan migrate    # migraciones
```

- App: http://localhost:8080 · Catálogo de componentes: http://localhost:8080/ui · Correos: http://localhost:8025
- Usar `-u 1000` en `exec` para no crear archivos con dueño root.

## Reglas para pantallas (frontend)

- Toda vista usa un layout: `<x-layouts.paciente>`, `<x-layouts.panel>` o `<x-layouts.invitado>`, con `titulo`.
- Usar los componentes de `resources/views/components/` (`x-button`, `x-input`, `x-select`, `x-date`, `x-alert`, `x-card`, `x-empty-state`, `x-estado-turno`, `x-modal`, `x-slot-picker`). Si falta algo, extender el componente con su test, no copiar estilos en la vista.
- Colores solo con los tokens del tema (`brand-600`, `ink-900`, `cream-50`, `surface`, `line`, `danger-700`, etc.). Nunca hexadecimales sueltos, `style=""` con colores ni Tailwind por CDN. `brand-300` nunca como color de texto.
- Nunca armar clases de Tailwind con variables (`"bg-{$x}-100"`): usar clases completas y literales.
- Rutas con nombre; los ítems de menú salen de `app/Support/Navegacion.php` y aparecen solo si la ruta existe.
- Accesibilidad: etiquetas visibles, foco visible, objetivos de 44 px, contraste AA.
- Íconos solo de Phosphor (codeat3/blade-phosphor-icons); nunca emojis ni símbolos unicode como íconos.
- Textos para el usuario en español rioplatense neutro ("Reservá", "Elegí").

## Forma de trabajo

- TDD: primero el test que falla, después la implementación; la suite completa tiene que quedar en verde y Pint limpio.
- Ramas `feat/SCRUM-<n>-<descripcion>` desde `main`; PR a `main`. Commits y título del PR con prefijo convencional y ticket: `feat(SCRUM-<n>): …`, `fix(SCRUM-<n>): …`, `docs: …`, `chore: …`.
- No modificar migraciones ya mergeadas: crear una migración nueva.
- No commitear `.env` ni credenciales.
- Los mensajes de commit y las descripciones de PR no llevan atribuciones a herramientas ni links de sesiones.
