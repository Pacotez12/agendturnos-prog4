# Sprint 2 — Agenda, seguimiento y alta disponibilidad

| | |
|---|---|
| **Fechas planificadas** | 19/10/2026 – 30/10/2026 |
| **Objetivo** | Gestión de agenda de la profesional, seguimiento del paciente, arquitectura B, HAProxy con health checks y failover en vivo; entrega del Avance 3 (30/10). |
| **Equipo** | Marco Brizuela (Scrum Master), Juan Morán (backend), Enzo Ferrari (frontend), Julio Ybañez (DevOps), Félix Villasanti (QA) |

> Estado al 23/10/2026. Se completa al cierre del sprint.

## 1. Resultado

**Completado (17 de 20 tickets):**

| Ticket | Historia / tarea | Responsable | Entrega |
|---|---|---|---|
| SCRUM-13 | HU-03 Catálogo de servicios | Marco Brizuela | PR #8 |
| SCRUM-18 | HU-11 Disponibilidad horaria | Marco Brizuela | PR #9 |
| SCRUM-39 | Especialidades y servicios por profesional | Marco Brizuela | PR #10 |
| SCRUM-40 | Modelos Eloquent, Enums y factories | Marco Brizuela | PR #4 |
| SCRUM-26 | Sesiones centralizadas en Redis | Julio Ybañez | Verificado con el login en A y B |
| SCRUM-29 | Arquitectura B: Apache + mod_php | Julio Ybañez | PR #14 |
| SCRUM-30 | HAProxy: balanceo, health checks y Stats en 8404 | Julio Ybañez | PR #15 |
| SCRUM-31 | Demo de failover en vivo | Julio Ybañez | PR #16 (`docs/demo-failover.md`) |
| SCRUM-14 | HU-04 Consultar días y horarios disponibles | Enzo Ferrari | PR #17, ajustes en #18 |
| SCRUM-15 | HU-05 Solicitar turno | Marco Brizuela | PR #19 |
| SCRUM-17 | HU-07 Confirmar, reprogramar o cancelar un turno | Marco Brizuela | PR #20 |
| SCRUM-21 | HU-09 Notificación de turno confirmado | Marco Brizuela | PR #21 |
| SCRUM-22 | HU-10 Recordatorio de consulta próxima | Marco Brizuela | PR #21 |
| SCRUM-41 | HU-13 Agendar turno en nombre de un paciente (operador) | Marco Brizuela | PR #22 |
| SCRUM-19 | HU-12 Ver datos del turno y del paciente | Marco Brizuela | PR #23 |
| SCRUM-20 | HU-08 Consultar estado de mis turnos | Marco Brizuela | PR #24 |
| SCRUM-16 | HU-06 Ver agenda de turnos | Marco Brizuela | PR #25 |

**Abiertos:**

| Ticket | Estado |
|---|---|
| SCRUM-32 Ceremonias del Sprint 2 y documento del Avance 3 | Este reporte; falta la review y la retrospectiva con el equipo. |
| SCRUM-28 Ceremonias del Sprint 1 | Falta completar la retrospectiva del Sprint 1 (`sprint-1.md`, sección 6). |
| SCRUM-37 QA del Sprint 1 | Plan y casos de prueba en curso. |

**Replanificado durante el sprint:**
- HU-09 y HU-10 (notificaciones), previstas para el Sprint 3, se adelantaron a este sprint.
- HU-05, HU-07, HU-09 y HU-10 (backend) y HU-06, HU-08 y HU-12 (frontend) se reasignaron a Marco Brizuela para llegar con el flujo completo del turno al Avance 3.

## 2. Entregables del Avance 3

| Requisito | Evidencia |
|---|---|
| Ceremonias ágiles y tablero actualizado | Este reporte, `sprint-1.md` y el tablero de Jira (proyecto `SCRUM`). |
| Configuración e integración de HAProxy | `docker/haproxy/haproxy.cfg`: `roundrobin` entre A y B en el puerto 8000, health check contra `/health`, `option redispatch`, Stats en el 8404 y cabecera `X-Servidor`. |
| Arquitecturas A y B activas | A: Nginx + PHP-FPM (puerto 8080). B: Apache + mod_php (puerto 8081). Comparten código, PostgreSQL y Redis; la sesión sirve en los dos. |
| Demostración de failover en vivo | Guion en `docs/demo-failover.md`, ensayado con caída y recuperación de A y de B: 0 errores y la sesión se mantiene. |

## 3. Trabajo técnico destacado

- **Flujo completo del turno:** el paciente consulta horarios libres, solicita el turno y lo sigue en "Mis turnos"; recepción o la profesional lo confirma, reprograma o cancela desde el panel, y el sistema avisa por correo.
- **Máquina de estados** en `EstadoTurno::puedePasarA()`: solicitado, confirmado, atendido, ausente y cancelado. Cualquier otra transición la rechaza el backend. Cada cambio queda en el historial con fecha, autor y detalle.
- **Concurrencia resuelta en la base:** si dos personas reservan el mismo horario a la vez, la restricción de exclusión de PostgreSQL rechaza la segunda y la aplicación muestra un aviso en lugar de un error.
- **Notificaciones por cola:** los correos se envían en segundo plano (servicios `queue` y `scheduler`), quedan registrados como pendiente, enviado o fallido, y no se duplican. El recordatorio sale 24 horas antes.
- **Agendamiento por operador (HU-13):** recepción carga turnos para pacientes que llaman o vienen al consultorio, incluso sin cuenta, con la misma búsqueda de horarios libres.
- **Agenda del panel:** vistas por día y por semana, filtros por estado y cola de solicitudes por confirmar.

## 4. Métricas

| Métrica | Valor |
|---|---|
| Tickets comprometidos | 20 |
| Tickets completados al 23/10 | 17 (85 %) |
| Pull Requests integrados (#12 a #25) | 14 |
| Tests automatizados | 228, todos en verde (129 al cierre del Sprint 1) |
| Migraciones | 17 |

## 5. Impedimentos

- Las historias de backend y frontend del flujo del turno se concentraron en una persona para llegar a la fecha del Avance 3. Para el Sprint 3 conviene repartir de nuevo la carga.
- Los tres Pull Requests de HAProxy y la arquitectura B dependían uno del otro; al integrarlos hubo que resolver conflictos entre ellos.

## 6. Sprint Review

> Completar en la reunión de revisión: qué se mostró, comentarios recibidos y ajustes al backlog.

- Fecha:
- Asistentes:
- Demostración: reserva del paciente, confirmación desde el panel, correo de confirmación, agenda por día y por semana, failover de HAProxy.
- Comentarios:
- Cambios al backlog:

## 7. Retrospectiva

> Completar con el equipo.

**Qué salió bien**
-

**Qué se puede mejorar**
-

**Acciones para el Sprint 3** (con responsable)
-
