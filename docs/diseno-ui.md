# AgendTurnos — Diseño de interfaz

Estándar visual y de interacción de la plataforma. Toda pantalla nueva se construye con estas reglas y con los componentes de la sección 5. Si algo no está cubierto, se decide en equipo y se agrega acá.

> Estado: aprobado el 2026-10-18. Se implementa en SCRUM-38 (layout base y componentes).

---

## 1. Principios

1. **La agenda vive en el sistema.** Todo turno, venga de la web, del teléfono o de WhatsApp, termina cargado acá. La interfaz tiene que hacer eso más rápido que anotarlo a mano.
2. **Dos públicos, una sola identidad.** El paciente usa el celular y reserva pocas veces; recepción y las doctoras usan la PC todo el día. Mismos colores y componentes, distinta densidad.
3. **Claridad antes que decoración.** Un botón principal por pantalla, estados con texto, errores al lado del campo.
4. **Accesible por defecto.** Contraste WCAG AA, foco visible, navegable con teclado, objetivos táctiles de 44 px como mínimo.

## 2. Usuarios y canales

| Usuario | Dispositivo principal | Qué hace |
|---|---|---|
| Paciente | Celular | Se registra, reserva para sí o para un familiar, sigue y cancela sus turnos |
| Profesional | PC del consultorio | Ve su agenda, confirma o rechaza solicitudes, marca atendido o ausente, define su disponibilidad, carga turnos |
| Funcionario (recepción) | PC del consultorio | Ve la agenda de todas las profesionales, carga turnos de pacientes que llaman o vienen, administra servicios |

**Canales de reserva:**
- **Web (paciente):** canal principal de autoservicio. El turno nace `solicitado` y lo confirma la profesional o recepción.
- **Operador (profesional o funcionario):** para pacientes que llaman, escriben por WhatsApp o se presentan en el consultorio. El turno nace `confirmado`. Solo se ofrecen horarios dentro de la disponibilidad cargada, con la misma búsqueda de horarios libres que usa el paciente (sin sobreturnos).
- **WhatsApp:** en esta versión, solo un botón de consultas que abre el chat de la clínica (`wa.me`); no reserva. Etapas futuras: confirmaciones y recordatorios por WhatsApp (campo `canal` de `notificaciones`) y, después, un asistente conversacional que use los mismos servicios de agenda.

## 3. Identidad visual

### 3.1 Marca

Logo oficial de Glow Dental Protect en `public/images/` (versión color y versión blanca). No se redibuja ni se cambia de color fuera de esas dos versiones.

### 3.2 Color

Tokens definidos una sola vez en `resources/css/app.css` (bloque `@theme` de Tailwind 4). Ningún componente usa colores en hexadecimal sueltos.

| Token | Valor | Uso |
|---|---|---|
| `brand-700` | `#7A3F22` | Texto de acento sobre fondos claros de marca. En modo oscuro pasa a `#F0CFAE` (claro): no usarlo como fondo con texto blanco |
| `brand-hover` | `#7A3F22` (oscuro: `#A85C37`) | Hover y estado activo del botón principal. Contraste con blanco 8.2:1 y 4.9:1 |
| `brand-600` | `#995432` (Saddle Brown) | Botón principal, selección, foco, enlaces. Contraste con blanco 5.7:1 |
| `brand-300` | `#D8B78A` (Burlywood) | Fondos y acentos decorativos, avatares. **Nunca texto sobre blanco** (1.9:1) |
| `brand-100` | `#F3E6D3` | Fondo de elementos seleccionados del lado del paciente |
| `cream-50` | `#FBF7F2` | Fondo general del lado del paciente |
| `ink-900` | `#1F1A17` | Texto principal |
| `ink-500` | `#6B635D` | Texto secundario y etiquetas |
| `line` | `#E5E2DE` | Bordes y divisores del panel |
| `surface` | `#F7F6F4` | Fondo general del panel |
| `danger-700` | `#B91C1C` | Acciones destructivas y errores |

**Estados del turno** (iguales en agenda, listas, "Mis turnos" y correos; siempre con texto):

| Estado | Fondo | Texto |
|---|---|---|
| `solicitado` | `#FEF3C7` | `#92400E` |
| `confirmado` | `#DBEAFE` | `#1E3A8A` |
| `atendido` | `#DCFCE7` | `#166534` |
| `cancelado` | `#FEE2E2` | `#991B1B` |
| `ausente` | `#E5E7EB` | `#374151` |

### 3.3 Tipografía

- **Open Sans** (400, 600, 700) para todo el texto: es la de la papelería de la clínica.
- **Quicksand** (600, 700) solo para títulos del lado del paciente: repite la forma redondeada del logo.
- Texto base de 16 px en el celular y 14 px en el panel; interlineado 1.5.
- Las fuentes se sirven desde el propio proyecto (Vite), no desde un CDN.

### 3.4 Forma

- Lado del paciente: bordes redondeados (botones y chips tipo píldora, tarjetas de 16 px).
- Panel: bordes de 8 px, más compacto.
- Sombras mínimas; la jerarquía se marca con borde y espacio, no con sombras.

## 4. Layouts

### 4.1 Paciente (estilo "crema y marrón")

- **Celular:** barra superior con el logo y un **menú hamburguesa** que abre un panel lateral con: Inicio, Reservar turno, Mis turnos, Mi familia, Mi perfil, Cerrar sesión.
- **PC:** las mismas opciones visibles en la barra superior.
- **Botón flotante de WhatsApp** abajo a la derecha ("¿Dudas? Escribinos").
- **Inicio:** saludo, tarjeta del **próximo turno con su estado**, botón grande "Reservar un turno" y turnos recientes.
- **Reservar:** en este orden, profesional → servicio → día → horario → "para quién" (yo o un familiar) → confirmar. El botón final repite lo elegido ("Solicitar turno · Mar 21, 09:00"). Los horarios ocupados se muestran deshabilitados.
- **Mis turnos:** próximos e historial, con estado y opción de cancelar los propios.
- **Mi familia:** alta, edición y listado de familiares a cargo (pacientes sin cuenta con `responsable_id` = usuario). Al reservar se elige para quién es el turno.

### 4.2 Panel de profesional y recepción (estilo "blanco clínico")

- **Barra lateral fija** con: Agenda, Solicitudes (con contador), Nuevo turno, Pacientes, Disponibilidad, Servicios. Abajo, el usuario y su rol.
- Las opciones se filtran por rol: una profesional ve su propia agenda y disponibilidad; recepción ve todas las profesionales y administra servicios.
- **Agenda (pantalla principal):** navegación por día (anterior, siguiente, hoy), **grilla horaria con una columna por profesional**; cada turno ocupa su duración real y muestra paciente, servicio y estado. A la derecha, la cola **"Por confirmar"** con aceptar y rechazar en un clic.
- **Nuevo turno (operador):** buscar paciente por nombre o documento, o darlo de alta sin cuenta (nombre, apellido, documento, teléfono, fecha de nacimiento, responsable opcional); después profesional → servicio → día → horario. Nace `confirmado`.
- **Detalle del turno:** datos del paciente, del servicio y del historial de estados, con las acciones permitidas según el estado actual.
- En el celular, la barra lateral se colapsa en un menú.

## 5. Componentes

Blade Components en `resources/views/components/`. Ninguna vista repite estilos que ya resuelve un componente.

| Componente | Variantes y reglas |
|---|---|
| `<x-button>` | `primary` (marrón, uno por pantalla), `secondary` (borde), `ghost` (enlace), `danger` (solo acciones destructivas); estado deshabilitado y de carga. Prop `pill` para el lado del paciente |
| `<x-input>`, `<x-select>`, `<x-date>` | Etiqueta siempre visible arriba, ayuda opcional debajo, error debajo del campo en rojo, foco marrón visible |
| `<x-estado-turno>` | Recibe un `EstadoTurno` y pinta la etiqueta con los colores de la sección 3.2 |
| `<x-alert>` | `success`, `error`, `info`; los mensajes flash de la sesión se muestran con este componente |
| `<x-card>` | Contenedor con borde; variante del paciente con más radio |
| `<x-empty-state>` | Título, texto y una acción principal; ícono opcional en el slot `icono` |
| `<x-modal>` | Confirmaciones destructivas (cancelar turno); se cierra con Escape y devuelve el foco |
| `<x-slot-picker>` | Grilla de horarios libres y ocupados (Alpine.js); usada por paciente y operador |
| `<x-whatsapp-button>` | Botón flotante del lado del paciente |
| Íconos | Phosphor (`codeat3/blade-phosphor-icons`), `<x-phosphor-nombre class="size-4" aria-hidden="true" />`, nunca emojis |
| Layouts | `layouts.paciente`, `layouts.panel`, `layouts.invitado` (login, registro, catálogo público) |

## 6. Interacción y contenido

- **Español rioplatense neutro** ("Reservá", "Elegí"), frases cortas, sin jerga técnica.
- Cada acción da respuesta: mensaje de éxito, error junto al campo o estado de carga en el botón.
- Conflicto de horario: "Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista." y se recargan los horarios.
- Acciones destructivas siempre con confirmación.
- Fechas en formato largo para personas ("Martes 21 de octubre, 09:00") y zona horaria `America/Asuncion`.
- Animaciones cortas (150–250 ms) solo para abrir menús, modales y cambios de estado; se respetan las preferencias de movimiento reducido.

## 7. Accesibilidad y responsive

- Contraste mínimo 4.5:1 en texto; el Burlywood no se usa para texto.
- Foco visible en todos los elementos interactivos; todo se puede usar con teclado.
- Objetivos táctiles de 44 × 44 px como mínimo.
- Imágenes con texto alternativo; íconos solo decorativos con `aria-hidden`.
- Se diseña primero para celular; puntos de quiebre de Tailwind `sm` (640), `md` (768), `lg` (1024). Sin scroll horizontal.

## 8. Cambios de alcance que surgen de este diseño

| Cambio | Historia |
|---|---|
| Agendamiento por operador (profesional o funcionario), nace `confirmado`, solo dentro de la disponibilidad | Nueva historia HU-13 |
| "Mi familia": el paciente gestiona familiares a cargo y reserva para ellos | Se suma a HU-01 / HU-05 |
| Elección de profesional al reservar (la clínica tiene dos) | Se suma a HU-04 / HU-05 |
| Botón de consultas por WhatsApp | Parte de SCRUM-38 |
