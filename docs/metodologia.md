# Metodología de trabajo

Cómo organiza el equipo el desarrollo de Glow Dental Protect. Complementa a [`arquitectura.md`](arquitectura.md), que cubre lo técnico, y a los reportes de cada sprint en [`sprints/`](sprints/).

## 1. Marco de trabajo

Scrum, con sprints de dos semanas y un tablero en Jira (proyecto `SCRUM`). Se eligió Scrum porque el sistema se puede entregar en incrementos funcionales y cada hito del proyecto coincide con el cierre de un sprint.

## 2. Equipo y roles

| Rol | Integrante | Responsabilidades |
|---|---|---|
| Scrum Master / Project Manager | Marco Brizuela | Metodología, tablero, control del alcance, coordinación; desarrollo full stack |
| Backend & Software Architect Lead | Juan Morán | Modelo de datos, migraciones, lógica en Laravel |
| DevOps & Cloud Engineer | Julio Ybañez | Docker, Stack en Portainer, HAProxy, arquitectura B, failover |
| Frontend & QA Engineer | Enzo Ferrari | Vistas e interfaz |
| Frontend & QA Engineer | Félix Villasanti | Pruebas funcionales, plan de pruebas, benchmark |

## 3. Artefactos

- **Product Backlog:** historias de usuario en Jira con el formato "Como… quiero… para…" y criterios de aceptación. Se agrupan en épicas: Usuarios y autenticación, Servicios, Agendamiento, Gestión de agenda, Notificaciones, Seguimiento e Infraestructura.
- **Sprint Backlog:** los tickets asignados a cada sprint en Jira, con su objetivo.
- **Incremento:** lo integrado en la rama `main` al cierre del sprint, con todos los tests en verde.
- **Registro de cambios de alcance:** [`alcance-cambios.md`](alcance-cambios.md). Toda funcionalidad nueva se evalúa ahí antes de entrar al backlog.

## 4. Tablero

| Columna | Significa |
|---|---|
| Por hacer | Ticket planificado, sin empezar |
| En curso | Alguien lo está desarrollando |
| En revisión | Terminado por quien lo desarrolló; lo prueba QA o un compañero |
| Finalizado | Cumple la definición de terminado |

Etiquetas: `descartado` (se decidió no hacerlo) y `postergado` (fuera del alcance de este semestre, queda en el backlog).

## 5. Definición de terminado

Un ticket pasa a Finalizado cuando:

1. Cumple sus criterios de aceptación.
2. Tiene tests automatizados que fallaban antes del cambio y pasan después.
3. Pasó la integración continua (tests y estilo con Pint) en su pull request.
4. Se integró a `main` por pull request.
5. Si cambia la interfaz, se revisó en claro, oscuro y celular.
6. `docs/arquitectura.md` tiene su fila en el registro de cambios.

## 6. Flujo de trabajo en el código

- **Ramas:** una por ticket desde `main` (`feat/…`, `fix/…`, `docs/…`).
- **Commits:** prefijos `feat:`, `fix:`, `chore:` y `docs:`, con el número de ticket cuando corresponde (`feat(SCRUM-46): …`).
- **Pull requests:** descripción con qué cambia y por qué. Se integran con squash cuando la integración continua pasa.
- **Integración continua:** GitHub Actions corre toda la suite de tests sobre PostgreSQL en cada pull request.
- **Desarrollo guiado por tests:** primero el test que falla, después el código mínimo que lo hace pasar.

## 7. Ceremonias

| Ceremonia | Cómo se hace |
|---|---|
| Planificación | Al inicio de cada sprint: objetivo y tickets en Jira |
| Seguimiento diario | Asincrónico, por el grupo del equipo: qué hice, qué sigue, qué me frena |
| Sprint Review | Al cierre: demostración de lo integrado y ajustes al backlog |
| Retrospectiva | Al cierre: qué salió bien, qué mejorar y acciones con responsable |

Lo que se decide en review y retrospectiva queda en el reporte de cada sprint.

## 8. Sprints

| Sprint | Fechas | Objetivo | Reporte |
|---|---|---|---|
| Sprint 1 · Base | 02/10 – 16/10 | Entorno Docker, modelo de datos, autenticación, servicios y disponibilidad; sesiones en Redis y despliegue inicial en Portainer | [`sprint-1.md`](sprints/sprint-1.md) |
| Sprint 2 · HAProxy | 19/10 – 30/10 | Flujo completo del turno, agenda, arquitectura B, HAProxy con failover | [`sprint-2.md`](sprints/sprint-2.md) |
| Sprint 3 · Cierre final | 30/10 – 13/11 | Mi familia, plan y ejecución de pruebas, despliegue final, cierre metodológico y documentación | — |
