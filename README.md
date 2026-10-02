# AgendTurnos — Glow Dental Protect

Sistema web de agendamiento de turnos odontológicos para **Glow Dental Protect** (Asunción, Paraguay).

## Stack

- Laravel 13 (PHP 8.4)
- PostgreSQL
- Redis (sesiones centralizadas)
- Docker / docker-compose, desplegado como Stack en Portainer
- HAProxy como balanceador frontal (stats en puerto 8404)

## Puesta en marcha

1. Copiar variables de entorno: `cp .env.example .env`
2. Construir y levantar los contenedores: `make build && make up` (o `docker compose up -d --build`)
3. Generar la clave de la aplicación: `make artisan cmd=key:generate`
4. Correr migraciones: `make migrate`
5. Acceder en `http://localhost:8080` (Mailpit: `http://localhost:8025`).

## Gestión

Metodología Scrum, tablero en Jira.
