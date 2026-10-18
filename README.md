# Agendamiento de turnos — Glow Dental Protect

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

Los estilos y el JavaScript (Tailwind y Alpine.js, compilados con Vite) los compila el contenedor `node` al levantar el entorno, y los vuelve a compilar cada vez que se guarda un archivo. No hace falta tener Node instalado. Si una vista muestra `Vite manifest not found`, esperar a que termine la primera compilación (`docker compose logs -f node`) o correr `make assets`.

## Despliegue en Portainer

Portainer CE no soporta `build:` ni bind mounts relativos, por eso el Stack usa `docker-compose.portainer.yml`: imágenes preconstruidas y la ruta absoluta del proyecto.

1. Bajar el entorno de desarrollo para liberar puertos: `make down`
2. Preparar el proyecto (si no se hizo): `.env` con `APP_KEY`, `composer install` y `npm install && npm run build`.
3. Construir las imágenes: `make portainer-build`
4. Levantar Portainer CE:
   ```bash
   docker volume create portainer_data
   docker run -d -p 9443:9443 --name portainer --restart=always \
     -v /var/run/docker.sock:/var/run/docker.sock \
     -v portainer_data:/data portainer/portainer-ce:lts
   ```
5. Entrar a `https://localhost:9443` y crear el usuario admin (dentro de los 5 minutos de arrancar; el setup token sale en `docker logs portainer`). Elegir el entorno Docker local.
6. Stacks → Add stack → nombre `agendturnos` → Web editor → pegar `docker-compose.portainer.yml`.
7. En *Environment variables* definir:
   - `PROJECT_PATH`: ruta absoluta del proyecto vista por el daemon. En Docker Desktop (Windows): `/run/desktop/mnt/host/c/Users/<usuario>/agendturnos-prog4`.
   - `REDIS_PORT_FORWARD` (opcional): si el 6379 está ocupado, p. ej. `6381`.
8. Pulsar **Deploy the stack** y luego, en la consola del contenedor `app`: `php artisan migrate`
9. Verificar: el Stack figura activo con los 5 servicios en verde y `http://localhost:8080` responde.

## Gestión

Metodología Scrum, tablero en Jira.
