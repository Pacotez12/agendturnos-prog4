# Plan y resultados de pruebas

Plan de pruebas del sistema, matriz de cobertura por historia de usuario y resultado de la ejecución manual. Ticket: SCRUM-37.

## 1. Alcance y enfoque

- **Pruebas automáticas:** 365 tests (PHPUnit) sobre PostgreSQL. Corren en cada pull request con GitHub Actions; ningún cambio entra a `main` con un test en rojo.
- **Pruebas funcionales manuales:** recorrido de los flujos reales contra la aplicación levantada, con una sesión por rol: invitado, paciente, paciente con familiar a cargo (tutor), recepción y profesional.
- **Pruebas visuales:** las 31 pantallas en modo claro, modo oscuro y celular (390 px), por rol.
- **Seguridad:** accesos cruzados entre roles y entre profesionales, CSRF, enumeración de cuentas y cabeceras HTTP.
- **Rendimiento y disponibilidad:** benchmark A contra B y ensayo de failover (ver [`../benchmark.md`](../benchmark.md) y [`../demo-failover.md`](../demo-failover.md)).

**Entorno:** stack completo en Docker (A, B, HAProxy, PostgreSQL, Redis, cola, scheduler, Mailpit), datos de `make demo`.

**Usuarios de prueba** (clave `password`): `funcionario@glowdental.test`, `profesional1@glowdental.test`, `profesional2@glowdental.test`, `tutor@glowdental.test` (con una hija a cargo) y `paciente@glowdental.test`.

## 2. Matriz de cobertura

Tests automáticos: archivos de `tests/Feature/` (entre paréntesis, cantidad de tests). Manual: casos de la sección 3.

| HU | Historia | Tests automáticos | Manual | Estado |
|---|---|---|---|---|
| HU-01 | Registro de paciente | RegistroPacienteTest (7) | M-01 a M-03 | Pasa |
| HU-02 | Inicio de sesión y acceso por rol | LoginTest (10), AccesoPorRolTest (7), RestablecerContrasenaTest (9) | M-04 a M-09 | Pasa |
| HU-03 | Catálogo de servicios | CatalogoServiciosTest (6), AdminServiciosTest (6), AdminEspecialidadesTest (8), ServiciosPorProfesionalTest (7) | M-10, M-11 | Pasa |
| HU-04 | Días y horarios disponibles | ReservarTurnoTest (11), DisponibilidadServiceTest (9) | M-12 | Pasa |
| HU-05 | Solicitar turno | SolicitarTurnoTest (15), TurnoIntegridadTest (9) | M-13, M-14 | Pasa |
| HU-06 | Agenda de turnos | AgendaTest (9) | M-17 | Pasa |
| HU-07 | Confirmar, reprogramar o cancelar | EstadosTurnoTest (16) | M-18 a M-22 | Pasa |
| HU-08 | Estado de mis turnos | MisTurnosTest (9) | M-15 | Pasa |
| HU-09 | Aviso de turno confirmado | NotificacionesTest (16) | M-23 | Pasa |
| HU-10 | Recordatorio de consulta | NotificacionesTest, HorarioSilenciosoTest | M-24 | Pasa |
| HU-11 | Disponibilidad horaria | DisponibilidadPanelTest (11) | M-27 | Pasa |
| HU-12 | Datos del turno y del paciente | DetalleTurnoTest (3) | M-19 | Pasa |
| HU-13 | Turno cargado por recepción | TurnoOperadorTest (13) | M-25 | Pasa |
| HU-14 | Familiares a cargo | SolicitarTurnoTest (casos de familiar) | M-13 | **Parcial**: se reserva para un familiar; falta el alta y la edición de familiares (SCRUM-42) |
| HU-15 | Avisos por WhatsApp | WhatsAppTest (10) | Envío real a un número verificado | **Parcial**: plantillas pendientes de aprobación en Meta (SCRUM-67) |
| HU-16 | Inicio del panel | PanelInicioTest (10) | M-26 | Pasa |
| HU-17 | Inicio del paciente | PacienteInicioTest (4) | M-03 | Pasa |
| HU-18 | Pacientes en el panel | PanelPacientesTest (6) | M-28 | Pasa |
| HU-19 | Estadísticas | — | — | Descartada |
| HU-20, HU-21, HU-24 | Respuesta por WhatsApp, bot, avisos push | — | — | Postergadas |
| HU-22 | Reenviar recordatorio | NotificacionesTest, PanelInicioTest | M-24 | Pasa |
| HU-23 | Panel instalable (PWA) | PwaTest (7) | M-29 | Pasa |
| HU-25 | Horario silencioso | HorarioSilenciosoTest (6) | Solo automático | Pasa |
| HU-26 | Límites y protección | LimitesYAbusoTest (8) | M-16 | Pasa |
| HU-27 | Celular paraguayo | CelularParaguayFeatureTest (4), Unit/TelefonoTest (5) | M-02 | Pasa |
| HU-28 | Privacidad y consentimiento | PrivacidadTest (4) | M-01 | Pasa |

Fuera de las historias: API REST (ServiciosApiTest, ProfesionalesApiTest, DisponibilidadApiTest, TokenApiTest, MisTurnosApiTest, DocumentacionApiTest), navegación sin enlaces rotos (NavegacionTest), páginas de error (ErrorPagesTest), modo oscuro, reloj y componentes.

## 3. Casos manuales y resultado (25/10/2026)

| ID | Caso | Esperado | Resultado |
|---|---|---|---|
| M-01 | Registro sin aceptar la privacidad | Se rechaza con mensaje | Pasa |
| M-02 | Registro con celular inválido (`12345`) | Se rechaza con mensaje | Pasa |
| M-03 | Registro válido | Crea la cuenta, entra y ve su inicio | Pasa |
| M-04 | Login con clave incorrecta | "El correo o la contraseña no son correctos." | Pasa |
| M-05 | Login de paciente | Va a `/inicio` | Pasa |
| M-06 | Login de recepción | Va a `/panel` | Pasa |
| M-07 | Login de profesional | Va a `/panel` | Pasa |
| M-08 | Paciente abre `/panel` | 403 | Pasa |
| M-09 | Invitado abre `/panel` | Redirige al login | Pasa |
| M-10 | Invitado abre portada, catálogo, privacidad, login y registro | 200 | Pasa |
| M-11 | Catálogo muestra servicios con duración | Visible | Pasa |
| M-12 | Paciente consulta horarios de un día | Lista horarios libres | Pasa |
| M-13 | Tutor solicita un turno para su hija | Turno en estado Solicitado | Pasa |
| M-14 | Mismo horario solicitado dos veces | El segundo se rechaza | Pasa |
| M-15 | Mis turnos y detalle del turno | Muestra la solicitud y su estado | Pasa |
| M-16 | Tercera solicitud pendiente de la misma cuenta | Se rechaza (tope de 2) | Pasa |
| M-17 | Agenda del día y Solicitudes | Muestran el pedido nuevo | Pasa |
| M-18 | Recepción confirma | Estado Confirmado | Pasa |
| M-19 | Detalle del turno en el panel | Datos del paciente y del responsable | Pasa |
| M-20 | Reprogramar sin fecha y a otro horario | Abre la pantalla y mueve el turno | Pasa |
| M-21 | Cancelar con motivo | Estado Cancelado con el motivo | Pasa |
| M-22 | Confirmar un turno cancelado | Se rechaza con mensaje | Pasa |
| M-23 | Correo de confirmación en Mailpit | Llega al responsable | Pasa |
| M-24 | Botón Recordar | Llega el recordatorio; un segundo envío inmediato se bloquea | Pasa |
| M-25 | Recepción agenda para un paciente sin cuenta | Turno Confirmado | Pasa |
| M-26 | Inicio del panel | Indicadores del día y de la semana | Pasa |
| M-27 | Disponibilidad | Horario semanal visible | Pasa |
| M-28 | Búsqueda de pacientes sin tildes (`gomez`) | Encuentra "Gómez" | Pasa |
| M-29 | Panel declara el manifest de la PWA | Presente | Pasa |

Los datos creados durante la prueba se borraron al terminar.

## 4. Defectos encontrados

| ID | Defecto | Severidad | Estado |
|---|---|---|---|
| D-01 | Con el entorno de desarrollo, si se reinicia el contenedor `node`, mientras recompila los assets el panel responde 500 ("Vite manifest not found") durante algunos segundos. En una corrida coincidió con el reinicio y falló la confirmación de un turno. | Media (solo entorno de desarrollo; afecta la demo si se reinicia en vivo) | Abierto: para la demo y Portainer, compilar los assets una vez (`npm run build`) y no usar `--watch` |

Defectos de la auditoría del 24/10 (16 puntos, entre ellos reprogramar sin fecha, avisos de turnos cancelados y tablas en celular): corregidos en el commit `acdb389` y en SCRUM-64 y SCRUM-65.

## 5. Pruebas pendientes

- Prueba real de los avisos por WhatsApp desde la aplicación, cuando Meta apruebe las plantillas (SCRUM-67).
- HU-14 completa, cuando esté el alta y la edición de familiares (SCRUM-42).
- Instalación de la PWA en un celular real con HTTPS.
