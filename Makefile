DOCKER_COMPOSE := docker compose
APP_SERVICE := app

.PHONY: help build up down restart logs ps sh composer-install artisan-% key fresh-db test ui swagger

help:
	@echo "Available targets:"
	@echo "  make build            Build images"
	@echo "  make up               Start services"
	@echo "  make down             Stop services"
	@echo "  make restart          Restart services (rebuild)"
	@echo "  make ps               List services status"
	@echo "  make logs             Follow logs"
	@echo "  make sh               Shell into app container"
	@echo "  make composer-install  Run composer install in container"
	@echo "  make key              Generate APP_KEY"
	@echo "  make fresh-db         Fresh migrate + seed"
	@echo "  make test             Run tests (php artisan test)"
	@echo "  make artisan-<cmd>     Run artisan command, e.g. make artisan-migrate"
	@echo "  make swagger           Print Swagger URL"
	@echo "  make ui               Print UI URL"

build:
	$(DOCKER_COMPOSE) build --pull

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

restart:
	$(DOCKER_COMPOSE) down
	$(DOCKER_COMPOSE) up -d --build

ps:
	$(DOCKER_COMPOSE) ps

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

ui:
	@echo "UI: http://localhost:8080/ui"

swagger:
	@echo "Swagger: http://localhost:8080/api/documentation"
