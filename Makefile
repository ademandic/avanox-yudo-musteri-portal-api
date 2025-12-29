#==============================================================================
# Portal API - Makefile
#==============================================================================
# Usage:
#   make help       - Show available commands
#   make dev        - Start development environment
#   make prod       - Start production environment
#   make down       - Stop all containers
#==============================================================================

.PHONY: help dev prod down build rebuild logs shell artisan migrate fresh test

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

# Default target
.DEFAULT_GOAL := help

#------------------------------------------------------------------------------
# Help
#------------------------------------------------------------------------------
help: ## Show this help
	@echo ''
	@echo 'Portal API - Docker Commands'
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  ${YELLOW}%-15s${RESET} %s\n", $$1, $$2}' $(MAKEFILE_LIST)

#------------------------------------------------------------------------------
# Development
#------------------------------------------------------------------------------
dev: ## Start development environment
	docker-compose up -d
	@echo "${GREEN}Development environment started!${RESET}"
	@echo "API: http://localhost:8080/api/health"

dev-build: ## Build and start development environment
	docker-compose up -d --build
	@echo "${GREEN}Development environment built and started!${RESET}"

#------------------------------------------------------------------------------
# Production
#------------------------------------------------------------------------------
prod: ## Start production environment
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
	@echo "${GREEN}Production environment started!${RESET}"

prod-build: ## Build and start production environment
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
	@echo "${GREEN}Production environment built and started!${RESET}"

#------------------------------------------------------------------------------
# Common Commands
#------------------------------------------------------------------------------
down: ## Stop all containers
	docker-compose down
	@echo "${GREEN}All containers stopped!${RESET}"

down-v: ## Stop all containers and remove volumes
	docker-compose down -v
	@echo "${GREEN}All containers and volumes removed!${RESET}"

restart: ## Restart all containers
	docker-compose restart
	@echo "${GREEN}All containers restarted!${RESET}"

build: ## Build containers without cache
	docker-compose build --no-cache

rebuild: down build dev ## Rebuild and restart

logs: ## View container logs
	docker-compose logs -f

logs-app: ## View app container logs
	docker-compose logs -f app

logs-nginx: ## View nginx container logs
	docker-compose logs -f nginx

#------------------------------------------------------------------------------
# Shell Access
#------------------------------------------------------------------------------
shell: ## Access app container shell
	docker-compose exec app sh

shell-nginx: ## Access nginx container shell
	docker-compose exec nginx sh

#------------------------------------------------------------------------------
# Laravel Commands
#------------------------------------------------------------------------------
artisan: ## Run artisan command (use: make artisan cmd="migrate")
	docker-compose exec app php artisan $(cmd)

migrate: ## Run migrations
	docker-compose exec app php artisan migrate
	@echo "${GREEN}Migrations completed!${RESET}"

migrate-fresh: ## Fresh migration with seeders
	docker-compose exec app php artisan migrate:fresh --seed
	@echo "${GREEN}Fresh migration completed!${RESET}"

seed: ## Run seeders
	docker-compose exec app php artisan db:seed
	@echo "${GREEN}Seeding completed!${RESET}"

cache-clear: ## Clear all caches
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	@echo "${GREEN}All caches cleared!${RESET}"

cache-optimize: ## Optimize for production
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	@echo "${GREEN}Caches optimized!${RESET}"

#------------------------------------------------------------------------------
# Composer
#------------------------------------------------------------------------------
composer-install: ## Install composer dependencies
	docker-compose exec app composer install

composer-update: ## Update composer dependencies
	docker-compose exec app composer update

composer-dump: ## Dump autoload
	docker-compose exec app composer dump-autoload

#------------------------------------------------------------------------------
# Testing
#------------------------------------------------------------------------------
test: ## Run tests
	docker-compose exec app php artisan test

test-coverage: ## Run tests with coverage
	docker-compose exec app php artisan test --coverage

#------------------------------------------------------------------------------
# Utilities
#------------------------------------------------------------------------------
ps: ## Show running containers
	docker-compose ps

stats: ## Show container stats
	docker stats --no-stream

health: ## Check API health
	@curl -s http://localhost:8080/api/health | jq .

cleanup: ## Remove unused Docker resources
	docker system prune -f
	@echo "${GREEN}Cleanup completed!${RESET}"
