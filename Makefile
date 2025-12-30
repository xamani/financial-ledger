DOCKER_COMPOSE := docker compose
APP_SERVICE := app

.PHONY: build up down restart logs sh composer-install artisan-% key fresh-db test

build:
	$(DOCKER_COMPOSE) build --pull

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

restart:
	$(DOCKER_COMPOSE) down
	$(DOCKER_COMPOSE) up -d --build

logs:
	$(DOCKER_COMPOSE) logs -f

sh:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) sh

composer-install:
	$(DOCKER_COMPOSE) run --rm $(APP_SERVICE) composer install

artisan-%:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan $*

key:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan key:generate

fresh-db:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan migrate:fresh --seed

test:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan test
