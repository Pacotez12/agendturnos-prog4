# Arquitectura Docker — AgendTurnos (Glow Dental Protect)

Este directorio contiene las configuraciones y Dockerfiles para los servicios web de la aplicación.

## Estructura de Arquitecturas

- `php-fpm/`: **Arquitectura A (Backend)** — PHP 8.4 FPM con extensiones `pdo_pgsql`, `pgsql`, `redis`, `zip`, `intl`, `bcmath`, `opcache`.
- `nginx/`: **Arquitectura A (Servidor Web)** — Nginx sirviendo `/public` y proxeando requests PHP a `app:9000`.
- *(Preparado para el futuro)* `apache/`: **Arquitectura B** — Apache 2.4 con `mod_php`.
- *(Preparado para el futuro)* `haproxy/`: Balanceador frontal distribuyendo tráfico entre Arquitectura A y B.
