.PHONY: up down build sh artisan migrate fresh logs portainer-build

up:
	docker compose up -d

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

logs:
	docker compose logs -f

# Imagenes con tag fijo para el stack de Portainer (docker-compose.portainer.yml)
portainer-build:
	docker build -t agendturnos-app:latest -f docker/php-fpm/Dockerfile .
	docker build -t agendturnos-web:latest -f docker/nginx/Dockerfile .
