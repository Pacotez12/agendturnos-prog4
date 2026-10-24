# Registro de cambios de alcance

La Declaración de Alcance del Avance 1 fija qué incluye el sistema y qué queda afuera. Cualquier funcionalidad nueva se evalúa antes de entrar al backlog. Este registro deja constancia de cada cambio: qué se pidió, por qué, qué impacto tiene y cómo se controló.

**Criterio de aceptación de un cambio:** entra solo si resuelve un problema real del agendamiento de la clínica, no agrega funcionalidades de un sistema clínico integral (historia clínica, facturación, inventario) y entra en el tiempo del semestre sin poner en riesgo los entregables comprometidos.

## Resumen

| ID | Fecha | Cambio | Tipo | Decisión | Tickets |
|---|---|---|---|---|---|
| CA-01 | 14/10/2026 | Varias profesionales en la agenda | Pasa de fuera a dentro del alcance | Aprobado | SCRUM-25, SCRUM-39, SCRUM-60 |
| CA-02 | 19/10/2026 | Rol de recepción y carga de turnos por operador | Ampliación | Aprobado | SCRUM-41 |
| CA-03 | 19/10/2026 | Familiares a cargo de una cuenta | Ampliación | Aprobado | SCRUM-42 |
| CA-04 | 23/10/2026 | Avisos de turno por WhatsApp (solo salientes) | Pasa de fuera a dentro del alcance, acotado | Aprobado con límites | SCRUM-43, SCRUM-50 |
| CA-05 | 23/10/2026 | Bot de WhatsApp y respuesta a recordatorios | Ampliación | Postergado | SCRUM-48, SCRUM-49 |
| CA-06 | 24/10/2026 | Estadísticas del consultorio | Ampliación | Rechazado | SCRUM-47 |
| CA-07 | 24/10/2026 | Panel instalable (PWA) | Ampliación menor | Aprobado | SCRUM-51 |
| CA-08 | 24/10/2026 | API REST | Entregable técnico | Aprobado | SCRUM-61 |

## Detalle

### CA-01. Varias profesionales en la agenda

- **Alcance original:** "Gestión de múltiples profesionales" figuraba fuera de alcance; la primera versión apuntaba a una sola profesional.
- **Motivo del cambio:** la ficha de la empresa describe el problema como el agendamiento "para los diferentes profesionales y sus áreas". Al relevar la clínica se confirmó que atienden dos profesionales con servicios distintos. Un sistema para una sola agenda no resolvía el problema real.
- **Impacto:** el modelo de datos ya separaba `profesionales` de `turnos` (relación 1:N del Avance 1). Se sumó la relación `profesional_servicio` y la disponibilidad por profesional. La agenda, la reserva y el panel filtran por profesional. No cambia el resto del alcance.
- **Control:** se incorporó en el modelo de datos (SCRUM-25), antes de construir pantallas, para no rehacer trabajo. Varias sucursales sigue fuera de alcance.

### CA-02. Rol de recepción y carga de turnos por operador

- **Alcance original:** el sistema estaba pensado para que el paciente pida su turno y la profesional lo gestione.
- **Motivo del cambio:** parte de los pacientes sigue pidiendo turno por teléfono o en persona. Sin una carga por operador, esos turnos quedaban fuera del sistema y la agenda volvía a ser incompleta, que es el problema que se quería resolver.
- **Impacto:** rol `funcionario` y pantalla "Nuevo turno" en el panel, que reutiliza la misma búsqueda de horarios libres.
- **Control:** se agregó en la revisión del Sprint 1 como HU-13.

### CA-03. Familiares a cargo de una cuenta

- **Motivo del cambio:** los menores no tienen correo ni cuenta propia; quien pide el turno es el padre, la madre o el tutor.
- **Impacto:** un paciente puede tener familiares a cargo y pedir turnos para ellos. Se ve en "Mis turnos" y en la ficha del paciente del panel.
- **Control:** se agregó en la revisión del Sprint 1 como HU-14.

### CA-04. Avisos de turno por WhatsApp

- **Alcance original:** "Integración con redes sociales (WhatsApp, Instagram, Facebook)" figuraba fuera de alcance.
- **Motivo del cambio:** los pacientes de la clínica se comunican por WhatsApp y muchos no revisan el correo. Un recordatorio que no se lee no reduce las ausencias.
- **Qué entra (acotado):** solo mensajes **salientes** con plantilla aprobada por Meta, a través de la API oficial de WhatsApp Cloud: confirmación, cancelación, reprogramación y recordatorio del turno, y el recordatorio manual desde el panel.
- **Qué sigue afuera:** chat, lectura de mensajes entrantes, bot de reservas, Instagram y Facebook.
- **Impacto:** un servicio (`WhatsAppService`) que se llama desde el mismo job que envía el correo. El correo sigue siendo el canal principal: si WhatsApp falla, se registra y el aviso por correo sale igual.

### CA-05. Bot de WhatsApp y respuesta a recordatorios

- **Pedido:** que el paciente pueda pedir turno conversando por WhatsApp, y confirmar o cancelar respondiendo al recordatorio.
- **Decisión:** postergado. Requiere recibir mensajes (webhook público con HTTPS), manejar conversaciones y validar cada paso. Es un proyecto en sí mismo y pone en riesgo los entregables del semestre. Queda en el backlog para una versión posterior.

### CA-06. Estadísticas del consultorio

- **Pedido:** tablero con indicadores de turnos, ausencias y servicios más pedidos.
- **Decisión:** rechazado. Con el volumen de una clínica chica, los indicadores del día en el inicio del panel alcanzan. Se cerró el ticket con la etiqueta `descartado`.

### CA-07. Panel instalable (PWA)

- **Alcance original:** "Aplicación móvil nativa" figura fuera de alcance.
- **Por qué no lo contradice:** la PWA es la misma aplicación web, instalable desde el navegador, sin código nativo ni publicación en tiendas. Solo se ofrece en el panel del personal y no guarda datos sin conexión.

### CA-08. API REST

- **Motivo:** la API REST forma parte de los entregables técnicos del proyecto, junto con el despliegue y el balanceo.
- **Qué entra:** consulta pública de servicios, profesionales y horarios libres, y consulta de "mis turnos" del paciente con token. El detalle está en [`api.md`](api.md).
- **Qué sigue afuera:** pedir, confirmar o cancelar turnos por la API. Esas acciones ya tienen sus reglas en la aplicación web y duplicarlas no agrega valor en este semestre.

## Sigue fuera de alcance

Historia clínica, facturación y contabilidad, inventario, varias sucursales, app móvil nativa, chat y bot por redes sociales, pagos en línea, telemedicina, inteligencia artificial y marketing.
