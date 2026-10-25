# Demo de failover en vivo (Avance 3)

Guion para mostrar la tolerancia a fallos: HAProxy reparte el tráfico entre la arquitectura A (Nginx + PHP-FPM) y la B (Apache + mod_php). Se apaga un nodo con `docker stop`, HAProxy lo marca DOWN, el tráfico pasa al nodo sano y el usuario sigue logueado. Al levantar el nodo, vuelve a UP solo. Ticket: SCRUM-31.

**Duración:** unos 5 minutos. **Necesita:** el entorno levantado con `docker compose up -d`, un navegador y una terminal.

## Por qué funciona

- **HAProxy** (`docker/haproxy/haproxy.cfg`) pide `GET /health` a cada nodo cada 3 s. Con 3 fallos seguidos lo marca DOWN y deja de mandarle tráfico; con 2 aciertos lo vuelve a UP.
- **`/health`** responde 200 solo si el nodo llega a PostgreSQL y a Redis.
- **La sesión vive en Redis**, no en el nodo: cualquier nodo reconoce al usuario.
- **`option redispatch`**: si una petición cae en el nodo recién apagado antes de que figure DOWN, HAProxy la reintenta en el otro. El usuario no ve errores.

## Preparación (10 minutos antes)

1. Dejar todo listo con un solo comando. Levanta el stack, compila los assets **una vez** y frena el contenedor `node` (en modo `--watch`, cada recompilación deja el panel en error 500 unos segundos), aplica migraciones y carga los datos de demo con turnos de ese día:
   ```bash
   make demo-listo
   ```
   Termina con "Listo: A, B y HAProxy responden". Durante la demo **no** reiniciar `node` ni todo el stack: solo `docker stop`/`docker start` del nodo que se quiere tirar.
3. Abrir **Stats** en http://localhost:8404: `a_nginx` y `b_apache` en verde (UP).
4. Abrir la app en http://localhost:8000/login e iniciar sesión con `funcionario@glowdental.test` / `password`. Dejar abierto **Servicios** del panel. Si cambiaste `HAPROXY_PORT_FORWARD` en `.env`, usá ese puerto en lugar del 8000 (también en el loop).
5. Abrir una terminal con el **loop de tráfico** (una petición por segundo; muestra hora, código y nodo que respondió):

   Git Bash:
   ```bash
   while true; do echo "$(date +%T) $(curl -s -o /dev/null -w '%{http_code} %header{x-servidor}' http://localhost:8000/)"; sleep 1; done
   ```
   PowerShell:
   ```powershell
   while ($true) { $r = curl.exe -s -o NUL -w "%{http_code} %header{x-servidor}" http://localhost:8000/; "$(Get-Date -Format HH:mm:ss) $r"; Start-Sleep 1 }
   ```
6. Tener otra terminal lista para `docker stop` / `docker start`.

Pantalla sugerida: Stats a la izquierda, la app arriba a la derecha y el loop abajo a la derecha.

## Guion

| # | Hacer | Decir | Se ve |
|---|---|---|---|
| 1 | Mostrar Stats y el loop | "HAProxy reparte en roundrobin entre la arquitectura A, Nginx con PHP-FPM, y la B, Apache con mod_php. Las dos usan el mismo código, la misma base y el mismo Redis." | Ambos nodos en verde. El loop alterna `a_nginx` y `b_apache` |
| 2 | En la app, recorrer un par de páginas del panel | "Estoy logueado como recepción. Cada página la puede atender un nodo distinto y la sesión se mantiene porque vive en Redis." | Navegación normal |
| 3 | `docker stop agendturnos-apache-1` | "Simulo la caída de la arquitectura B apagando su contenedor." | El loop sigue en 200, solo con `a_nginx` |
| 4 | Esperar en Stats | "HAProxy chequea `/health` cada 3 segundos. A los 3 fallos lo marca DOWN." | `b_apache` pasa a rojo, **DOWN**, en ~10 s |
| 5 | Navegar en la app | "El usuario no perdió la sesión ni vio errores: todo el tráfico va al nodo sano." | Sigue logueado; las páginas cargan |
| 6 | `docker start agendturnos-apache-1` | "Levanto el nodo de nuevo. No toco HAProxy." | — |
| 7 | Esperar en Stats | "Con 2 chequeos correctos vuelve solo a UP." | `b_apache` en verde, **UP**, en ~15-20 s. El loop vuelve a alternar |

Variante: hacer lo mismo con la arquitectura A (`docker stop agendturnos-web-1` / `docker start agendturnos-web-1`). Se comporta igual.

## Tiempos esperados

| Evento | Tiempo |
|---|---|
| `docker stop` → DOWN en Stats | ~10 s |
| `docker start` → UP en Stats | 8-20 s (el nodo arranca en frío) |
| Errores vistos por el usuario | 0 |

## Si algo sale mal

| Problema | Causa | Solución |
|---|---|---|
| El loop muestra `503` y los dos nodos están en rojo | La app no está lista: los assets se están compilando o la base no está migrada | `docker compose logs node` hasta ver `built in`; `php artisan migrate --seed` |
| Un nodo figura `MAINT` en lugar de `DOWN` | HAProxy perdió el nombre del contenedor | `docker compose restart haproxy` y esperar a que los dos estén en UP |
| El nodo no vuelve a UP | El contenedor no arrancó | `docker compose ps`; `docker compose up -d apache` (o `web`) |
| La app pide login después de la caída | La sesión no está en Redis | Revisar `SESSION_DRIVER=redis` en `.env` |
| `docker stop` dice que no existe el contenedor | El nombre cambia si el proyecto no se llama `agendturnos` | `docker compose ps` muestra los nombres reales; o usar `docker compose stop apache` |

## Registro de ensayos

**24/10/2026**, Linux (Fedora) con el stack completo, sesión de recepción iniciada por HAProxy y un pedido a `/panel/servicios` cada medio segundo:

- Caída de B (`docker stop agendturnos-apache-1`): DOWN a los 10 s, UP a los 7 s después de `docker start`. 37 pedidos, todos en 200, ninguno pidió login.
- Caída de A (`docker stop agendturnos-web-1`): DOWN a los 12 s, UP a los 6 s. 32 pedidos, todos en 200, ninguno pidió login.
- Si se usa el Stack de Portainer, antes de la demo correr `make portainer-build` (cambió la configuración de Nginx y de PHP-FPM) y `php artisan migrate`: desde el último despliegue se agregaron migraciones.


**19/10/2026**, Windows 11 con Docker Desktop y el código montado desde el disco:

- Caída de B (`docker stop agendturnos-apache-1`): DOWN a los 9 s, UP a los 17 s después de `docker start`. 0 errores en el loop.
- Caída de A (`docker stop agendturnos-web-1`) con sesión iniciada, pidiendo `/panel/servicios`: DOWN a los 10 s, UP a los 8 s. Todas las respuestas en 200, sin pedir login.

Salida del loop durante la caída de B (primer ensayo):

```
16:48:02 200 b_apache
16:48:06 200 a_nginx      <- docker stop agendturnos-apache-1 (16:48:04)
16:48:10 200 a_nginx
16:48:19 200 a_nginx      <- Stats: b_apache DOWN (16:48:17)
16:48:26 200 a_nginx      <- docker start agendturnos-apache-1 (16:48:25)
...
16:48:55 200 a_nginx      <- Stats: b_apache UP
```

Ese primer ensayo encontró dos problemas, corregidos en SCRUM-30. Una petición tardó 9 s esperando al nodo caído (se bajó `timeout connect` a 1 s), y `/health` tardaba ~2 s, cerca del límite del chequeo (se cambió `opcache.revalidate_freq` y se subió `timeout check`).
