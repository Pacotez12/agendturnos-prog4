# API REST v1 — Glow Dental Protect

Documentación de la API REST v1 para la plataforma de agendamiento de **Glow Dental Protect**.

La API permite a aplicaciones externas (clientes web, aplicaciones móviles, integraciones) consultar el catálogo de servicios, los profesionales odontólogos, las franjas horarias disponibles y gestionar los turnos de pacientes autenticados mediante tokens de Laravel Sanctum.

---

## 1. Información general

- **URL base:** `http://localhost:8080/api/v1`
- **Formato de intercambio:** JSON (`Content-Type: application/json`, `Accept: application/json`)
- **Zona horaria de referencia:** `America/Asuncion` (UTC-03:00 / UTC-04:00 según horario estacional)
- **Fechas y horas:** Formato estándar ISO 8601 (`YYYY-MM-DDTHH:mm:ssP`, ej. `2026-10-25T08:30:00-03:00`)

---

## 2. Autenticación

La API utiliza **Laravel Sanctum** con Bearer Tokens personales.

- Para obtener un token, envíe una solicitud `POST /api/v1/token` con las credenciales (`email`, `password`, `nombre_dispositivo`).
- Las solicitudes a endpoints protegidos deben incluir la cabecera HTTP:
  ```http
  Authorization: Bearer <su_token_sanctum>
  ```
- Para revocar el token activo, envíe `DELETE /api/v1/token` con la cabecera `Authorization`.

---

## 3. Límites de tasa (Rate Limiting)

- **Límite general de la API:** 60 solicitudes por minuto por usuario autenticado o por dirección IP (`HTTP 429 Too Many Requests`).
- **Límite de autenticación (`POST /api/v1/token`):** 5 intentos por minuto por combinación de correo electrónico e IP para prevenir ataques de fuerza bruta.

---

## 4. Códigos de estado HTTP y respuestas de error

Todas las respuestas de error devuelven formato JSON:

| Código | Significado | Motivo típico |
|---|---|---|
| `200 OK` | Petición exitosa | Consulta o inicio de sesión correcto |
| `204 No Content` | Sin contenido | Revocación de token exitosa |
| `401 Unauthorized` | No autenticado | Token ausente, inválido o revocado |
| `403 Forbidden` | Acceso denegado | Rol insuficiente (ej. funcionario/profesional intentando consultar `/mis-turnos`) |
| `404 Not Found` | Recurso no encontrado | Ruta inexistente o registro no hallado |
| `422 Unprocessable Entity` | Error de validación | Parámetros inválidos, fecha fuera de rango o profesional que no ofrece el servicio |
| `429 Too Many Requests` | Límite excedido | Exceso de solicitudes por minuto |

### Formato de error de validación (422)

```json
{
  "message": "La fecha seleccionada no puede ser en el pasado.",
  "errors": {
    "fecha": [
      "La fecha seleccionada no puede ser en el pasado."
    ]
  }
}
```

---

## 5. Tabla de endpoints

| Método | Endpoint | Autenticación | Rol | Descripción |
|---|---|---|---|---|
| `GET` | `/api/v1/servicios` | Pública | Cualquiera | Lista de tratamientos y servicios activos |
| `GET` | `/api/v1/profesionales` | Pública | Cualquiera | Lista de profesionales en orden configurado |
| `GET` | `/api/v1/disponibilidad` | Pública | Cualquiera | Consulta de franjas horarias libres |
| `POST` | `/api/v1/token` | Pública (con throttle 5/min) | Cualquiera | Emisión de token de acceso personal |
| `DELETE` | `/api/v1/token` | Bearer Token | Cualquiera | Revocación del token de acceso actual |
| `GET` | `/api/v1/mis-turnos` | Bearer Token | `paciente` | Listado paginado de turnos del paciente y sus dependientes |

---

## 6. Detalle de endpoints y ejemplos cURL

### 6.1 Catálogo de servicios

Devuelve los servicios odontológicos activos cuyas especialidades también se encuentren activas, con duración en minutos, precio en Guaraníes (Gs) e identificadores de los profesionales habilitados para realizarlos.

- **Método:** `GET`
- **Ruta:** `/api/v1/servicios`
- **Autenticación:** No requerida

#### Ejemplo de solicitud

```bash
curl -X GET "http://localhost:8080/api/v1/servicios" \
     -H "Accept: application/json"
```

#### Respuesta de ejemplo (`200 OK`)

```json
{
  "data": [
    {
      "id": 1,
      "nombre": "Limpieza y profilaxis",
      "descripcion": "Remoción de sarro y placa bacteriana.",
      "duracion_min": 30,
      "precio": 150000,
      "especialidad": {
        "id": 1,
        "nombre": "Odontología general"
      },
      "profesionales_ids": [1, 2]
    }
  ]
}
```

---

### 6.2 Lista de profesionales

Devuelve los profesionales odontólogos en el orden de visualización de la clínica (`orden`, `apellido`, `nombre`). Incluye sus especialidades y los servicios activos que ofrece cada uno. Por privacidad, **no expone correos ni teléfonos**.

- **Método:** `GET`
- **Ruta:** `/api/v1/profesionales`
- **Autenticación:** No requerida

#### Ejemplo de solicitud

```bash
curl -X GET "http://localhost:8080/api/v1/profesionales" \
     -H "Accept: application/json"
```

#### Respuesta de ejemplo (`200 OK`)

```json
{
  "data": [
    {
      "id": 1,
      "nombre": "Ana",
      "apellido": "García",
      "nombre_completo": "Ana García",
      "especialidades": [
        {
          "id": 1,
          "nombre": "Ortodoncia"
        }
      ],
      "servicios": [
        {
          "id": 2,
          "nombre": "Consulta de ortodoncia"
        }
      ]
    }
  ]
}
```

---

### 6.3 Consulta de disponibilidad

Calcula las franjas horarias libres para un profesional, un servicio y una fecha específica, respetando la disponibilidad semanal, turnos agendados y bloqueos de agenda.

- **Método:** `GET`
- **Ruta:** `/api/v1/disponibilidad`
- **Autenticación:** No requerida
- **Parámetros de consulta (Query Params):**
  - `profesional_id` (entero, obligatorio): ID del profesional.
  - `servicio_id` (entero, obligatorio): ID del servicio activo. Debe ser ofrecido por el profesional.
  - `fecha` (string, obligatorio, formato `YYYY-MM-DD`): Fecha a consultar. No puede ser en el pasado.

#### Ejemplo de solicitud

```bash
curl -X GET "http://localhost:8080/api/v1/disponibilidad?profesional_id=1&servicio_id=1&fecha=2026-10-25" \
     -H "Accept: application/json"
```

#### Respuesta de ejemplo (`200 OK`)

```json
{
  "data": [
    {
      "inicio": "2026-10-25T08:00:00-03:00",
      "fin": "2026-10-25T08:30:00-03:00"
    },
    {
      "inicio": "2026-10-25T08:30:00-03:00",
      "fin": "2026-10-25T09:00:00-03:00"
    }
  ]
}
```

---

### 6.4 Obtener token de acceso

Autentica al usuario mediante correo y contraseña y emite un token de acceso personal.

- **Método:** `POST`
- **Ruta:** `/api/v1/token`
- **Autenticación:** No requerida
- **Rate Limit:** 5 intentos por minuto
- **Cuerpo de la petición (JSON):**
  - `email` (string, obligatorio): Correo electrónico registrado.
  - `password` (string, obligatorio): Contraseña de la cuenta.
  - `nombre_dispositivo` (string, obligatorio): Identificador del dispositivo o cliente (ej. `"iPhone 15"`, `"Portal Web"`).

#### Ejemplo de solicitud

```bash
curl -X POST "http://localhost:8080/api/v1/token" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
       "email": "paciente@glowdentalprotect.com",
       "password": "password",
       "nombre_dispositivo": "Dispositivo Móvil"
     }'
```

#### Respuesta de ejemplo (`200 OK`)

```json
{
  "token": "1|q0p1E6kFp5X9xG8jB1rN4yZ8L2...",
  "token_type": "Bearer",
  "usuario": {
    "id": 5,
    "rol": "paciente"
  }
}
```

---

### 6.5 Revocar token de acceso

Invalida y elimina el token Bearer activo utilizado para la solicitud.

- **Método:** `DELETE`
- **Ruta:** `/api/v1/token`
- **Autenticación:** Bearer Token

#### Ejemplo de solicitud

```bash
curl -X DELETE "http://localhost:8080/api/v1/token" \
     -H "Authorization: Bearer 1|q0p1E6kFp5X9xG8jB1rN4yZ8L2..." \
     -H "Accept: application/json"
```

#### Respuesta de ejemplo (`204 No Content`)

*(Cuerpo vacío)*

---

### 6.6 Mis turnos

Devuelve los turnos correspondientes al paciente autenticado y a sus familiares dependientes a cargo (`$user->pacientesIds()`), paginados de a 20 registros.

- **Método:** `GET`
- **Ruta:** `/api/v1/mis-turnos`
- **Autenticación:** Bearer Token (rol `paciente` requerido)
- **Parámetros de consulta (Query Params):**
  - `historial` (opcional, booleano `1` / `0`):
    - `0` o no presente: muestra turnos próximos futuros (`inicio >= now`) en estado `solicitado` o `confirmado`.
    - `1`: muestra el historial de turnos pasados o finalizados (`atendido`, `cancelado`, `ausente`).
  - `page` (opcional, entero): Número de página para paginación.

#### Ejemplo de solicitud

```bash
curl -X GET "http://localhost:8080/api/v1/mis-turnos" \
     -H "Authorization: Bearer 1|q0p1E6kFp5X9xG8jB1rN4yZ8L2..." \
     -H "Accept: application/json"
```

#### Respuesta de ejemplo (`200 OK`)

```json
{
  "data": [
    {
      "id": 14,
      "inicio": "2026-10-25T14:30:00-03:00",
      "fin": "2026-10-25T15:00:00-03:00",
      "estado": {
        "valor": "confirmado",
        "etiqueta": "Confirmado"
      },
      "servicio": {
        "id": 1,
        "nombre": "Limpieza y profilaxis"
      },
      "profesional": {
        "id": 1,
        "nombre_completo": "Ana García"
      },
      "paciente": {
        "id": 3,
        "nombre": "Lucas Benítez"
      }
    }
  ],
  "links": {
    "first": "http://localhost:8080/api/v1/mis-turnos?page=1",
    "last": "http://localhost:8080/api/v1/mis-turnos?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost:8080/api/v1/mis-turnos",
    "per_page": 20,
    "to": 1,
    "total": 1
  }
}
```
