.PHONY: up down build sh artisan migrate fresh logs

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
