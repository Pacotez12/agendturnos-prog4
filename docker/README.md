# Arquitectura Docker — AgendTurnos (Glow Dental Protect)

Este directorio contiene las configuraciones y Dockerfiles para los servicios web de la aplicación.

## Estructura de Arquitecturas

- `php-fpm/`: **Arquitectura A (Backend)** — PHP 8.4 FPM con extensiones `pdo_pgsql`, `pgsql`, `redis`, `zip`, `intl`, `bcmath`, `opcache`.
- `nginx/`: **Arquitectura A (Servidor Web)** — Nginx sirviendo `/public` y proxeando requests PHP a `app:9000`.
- *(Preparado para el futuro)* `apache/`: **Arquitectura B** — Apache 2.4 con `mod_php`.
- *(Preparado para el futuro)* `haproxy/`: Balanceador frontal distribuyendo tráfico entre Arquitectura A y B.

## Sesiones compartidas entre nodos (Redis)

`SESSION_DRIVER=redis` (en `.env`) guarda la sesión en Redis en vez de en el disco de cada contenedor, así un usuario sigue logueado aunque la petición caiga en otro nodo `app`.

Verificación:

Si el puerto 6379 del host está ocupado, cambiar `REDIS_PORT_FORWARD` en `.env`. Sin `make` (Windows): `docker compose up -d --build --scale app=2 && docker compose restart web`.

```bash
make scale   # levanta 2 contenedores app y reinicia nginx

# 30 peticiones con la misma cookie: X-App-Node reparte entre las 2 IPs
# (nginx rota por worker, con pocas peticiones pueden caer todas en el mismo nodo)
for i in $(seq 1 30); do curl -s -c c.txt -b c.txt -D - -o /dev/null http://localhost:8080/ | grep -i x-app-node; done | sort | uniq -c

# Evidencia: una sola clave de sesión en Redis pese a cambiar de nodo
docker compose exec redis redis-cli --scan --pattern '*'
```
