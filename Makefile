.PHONY: up scale down build sh artisan migrate fresh demo logs assets portainer portainer-build

up:
	docker compose up -d

# Dos nodos app; nginx se reinicia para resolver ambas IPs de "app" y balancear entre ellas
scale:
	docker compose up -d --build --scale app=2
	docker compose restart web

down:
	docker compose down

build:
	docker compose build

sh:
	docker compose exec app sh

artisan:
	docker compose exec app php artisan $(cmd)

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh

demo:
	docker compose exec -u www-data app php artisan demo:cargar --fresh

logs:
	docker compose logs -f

# Compilacion unica de los assets (sin quedar mirando cambios)
assets:
	docker compose run --rm node sh -c "npm install && npm run build"

# Portainer CE (se instala una vez por maquina, fuera del stack de la aplicacion).
# Si el contenedor ya existe solo lo arranca. Panel: https://localhost:9443
portainer:
	docker start portainer 2>/dev/null || docker run -d -p 9443:9443 --name portainer --restart=always \
		-v /var/run/docker.sock:/var/run/docker.sock -v portainer_data:/data portainer/portainer-ce:lts

# Imagenes con tag fijo para el stack de Portainer (docker-compose.portainer.yml)
portainer-build:
	docker build -t agendturnos-app:latest -f docker/php-fpm/Dockerfile .
	docker build -t agendturnos-web:latest -f docker/nginx/Dockerfile .
	docker build -t agendturnos-apache:latest -f docker/apache/Dockerfile .
	docker build -t agendturnos-haproxy:latest -f docker/haproxy/Dockerfile .
