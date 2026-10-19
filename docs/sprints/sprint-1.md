# Sprint 1 — Base del sistema

| | |
|---|---|
| **Fechas planificadas** | 02/10/2026 – 16/10/2026 |
| **Cierre en Jira** | 19/10/2026 |
| **Objetivo** | Entorno Docker (Laravel + PostgreSQL + Redis), modelo de datos, autenticación, servicios, disponibilidad y solicitud de turnos; sesiones en Redis y despliegue inicial en Portainer. |
| **Equipo** | Marco Brizuela (Scrum Master), Juan Morán (backend), Enzo Ferrari (frontend), Julio Ybañez (DevOps), Félix Villasanti (QA) |

## 1. Resultado

**Completado en el sprint (6 tickets):**

| Ticket | Historia / tarea | Responsable | Entrega |
|---|---|---|---|
| SCRUM-24 | Entorno base Docker: Laravel + PostgreSQL + Redis + Nginx | Julio Ybañez | `cefc8f7` |
| SCRUM-25 | Modelo de datos: MER corregido, migraciones, seeders y tests de integridad | Juan Morán | PR #1 |
| SCRUM-27 | Despliegue inicial del stack en Portainer | Julio Ybañez | `docker-compose.portainer.yml` |
| SCRUM-11 | HU-01 Registro de paciente | Enzo Ferrari | PR #2 |
| SCRUM-12 | HU-02 Inicio de sesión y control de acceso por rol | Marco Brizuela | PR #7 |
| SCRUM-38 | Layout base y componentes UI | Marco Brizuela | PR #3, #5 |

**Pasaron al Sprint 2 (3 tickets):**

| Ticket | Motivo |
|---|---|
| SCRUM-26 Sesiones centralizadas en Redis | La configuración funciona (las sesiones se guardan en Redis, verificado con el login); falta cerrar el ticket con su evidencia. |
| SCRUM-28 Ceremonias del Sprint 1 | Este reporte; queda completar la retrospectiva con el equipo. |
| SCRUM-37 QA del Sprint 1 | Plan y casos de prueba en curso. |

**Replanificado durante el sprint:** HU-03 (catálogo), HU-04, HU-05 y HU-11 (disponibilidad) se movieron al Sprint 2 porque dependían del modelo de datos. HU-03, HU-11 y SCRUM-39 se completaron al inicio del Sprint 2.

## 2. Trabajo técnico destacado

- **Modelo de datos con integridad en la base:** 15 migraciones; la base de datos impide que dos turnos de una profesional se superpongan (restricción de exclusión con `btree_gist`) y que se reserve un servicio que la profesional no ofrece.
- **Capa de modelos** (SCRUM-40, tarea surgida en el sprint): modelos Eloquent, Enums y factories para todas las tablas.
- **Diseño de interfaz aprobado** (`docs/diseno-ui.md`): identidad de la clínica (logo y paleta oficial), estilo cálido para el paciente y clínico para el panel, componentes reutilizables.
- **Calidad:** desarrollo guiado por tests; al cierre del sprint la suite pasa completa. Revisión de cada Pull Request antes de integrarlo.
- **Documentación viva:** `docs/arquitectura.md` (arquitectura, modelo, decisiones y registro de cambios), `docs/guia-frontend.md` y plantilla de PR con checklist.

## 3. Métricas

| Métrica | Valor |
|---|---|
| Tickets comprometidos | 9 |
| Tickets completados | 6 (67 %) |
| Pull Requests integrados (02/10 – 19/10) | 11 |
| Tests automatizados al cierre | 129, todos en verde |

## 4. Impedimentos

- El modelo de datos (camino crítico) se entregó el 16/10, al final del sprint, y frenó las historias que dependían de él (registro, login, catálogo, disponibilidad). Se resolvió priorizando la capa de modelos (SCRUM-40) para destrabar al equipo.
- El sprint se planificó con fechas iniciales ya avanzadas, lo que dejó poco margen para el trabajo de interfaz.

## 5. Sprint Review

> Completar en la reunión de revisión: qué se mostró, comentarios recibidos y ajustes al backlog.

- Fecha:
- Asistentes:
- Demostración: registro, login por rol, catálogo de servicios, disponibilidad horaria.
- Comentarios:
- Cambios al backlog: se agregaron HU-13 (agendamiento por operador) y HU-14 (familiares a cargo) a partir de la revisión del diseño.

## 6. Retrospectiva

> Completar con el equipo.

**Qué salió bien**
-

**Qué se puede mejorar**
-

**Acciones para el Sprint 2** (con responsable)
-
