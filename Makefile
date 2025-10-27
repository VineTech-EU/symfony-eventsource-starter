DOCKER_COMPOSE = docker compose
DOCKER_EXEC = $(DOCKER_COMPOSE) exec -T
DOCKER_RUN = $(DOCKER_COMPOSE) run --rm -T

EXEC_APP = $(DOCKER_EXEC) -u www-data app
EXEC_PHP = $(EXEC_APP) php -d memory_limit=-1
EXEC_PG = $(DOCKER_EXEC) postgres

SYMFONY = $(EXEC_PHP) bin/console
COMPOSER = $(EXEC_PHP) /usr/local/bin/composer
WFI = $(DOCKER_EXEC) app wait-for-it

APP_SRC = src

##
## Project
## -------
##

init: ## Initialize the project (setup /etc/hosts)
	@./scripts/setup-hosts.sh || true

build: docker-compose.override.yml ## Build Docker images
	$(DOCKER_COMPOSE) pull --ignore-pull-failures
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 $(DOCKER_COMPOSE) build --pull app
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 $(DOCKER_COMPOSE) build --pull

kill: docker-compose.override.yml ## Kill and remove all containers and volumes
	$(DOCKER_COMPOSE) kill || true
	$(DOCKER_COMPOSE) down --volumes --remove-orphans

install: ## Install and start the project
install: init build start vendor
	$(MAKE) db
	$(MAKE) messenger-setup

reset: ## Stop and start a fresh install of the project
reset: kill
	$(MAKE) install

start: docker-compose.override.yml ## Start the project
	$(DOCKER_COMPOSE) up -d --remove-orphans --no-recreate

stop: docker-compose.override.yml ## Stop the project
	$(DOCKER_COMPOSE) stop

stop-worker: docker-compose.override.yml ## Stop consumers
	-$(DOCKER_COMPOSE) kill event_consumer command_consumer

start-worker: docker-compose.override.yml ## Start consumers
	$(DOCKER_COMPOSE) up -d event_consumer command_consumer

clean: ## Stop the project and remove generated files
clean: kill
	rm -rf vendor var/cache var/log

.PHONY: build kill install reset start stop clean

##
## Utils
## -----
##

php_sh: ## Enter in php container as www-data with zsh
	$(DOCKER_COMPOSE) exec -u www-data app zsh

cc: ## Clear cache
	$(SYMFONY) cache:clear

db: ## Reset the database and load fixtures
db: db-clean fixtures

db-clean: ## Reset the database
db-clean: db-wait stop-worker
	-$(SYMFONY) doctrine:database:drop --if-exists --force
	-$(SYMFONY) doctrine:database:create --if-not-exists
	$(SYMFONY) doctrine:migrations:migrate --no-interaction --allow-no-migration --quiet
	$(MAKE) start-worker

db-wait: ## Wait for database and RabbitMQ to be ready
	@$(WFI) postgres:5432 --timeout=30
	@$(WFI) rabbitmq:5672 --timeout=30

db-validate: ## Validate doctrine schema
db-validate: db-wait
	$(SYMFONY) doctrine:schema:validate

db-diff: ## Generate a new doctrine migration
db-diff: db-wait
	$(SYMFONY) doctrine:migrations:diff

db-migr: ## Run doctrine migrations
db-migr: db-wait
	$(SYMFONY) doctrine:migrations:migrate --no-interaction --allow-no-migration

messenger-setup: ## Setup messenger transports
messenger-setup: db-wait
	$(SYMFONY) messenger:setup-transports

fixtures: ## Load data fixtures
	$(SYMFONY) doctrine:fixtures:load --no-interaction

db-test: ## Reset test database (drop, create, migrate)
	$(SYMFONY) --env=test doctrine:database:drop --if-exists --force
	$(SYMFONY) --env=test doctrine:database:create
	$(SYMFONY) --env=test doctrine:migrations:migrate --no-interaction

.PHONY: db db-clean db-wait db-validate db-diff db-migr messenger-setup fixtures db-test

##
## Tests (Optimized)
## ------------------
##

# Standard test commands (with DB reset)
test: ## Run all tests (unit + integration + functional)
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit

tu: ## Run unit tests only (fast, no infrastructure)
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --testsuite=Unit

ti: ## Run integration tests (with DB reset)
ti: db-test
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --testsuite=Integration

tf: ## Run functional tests (with DB reset)
tf: db-test
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --testsuite=Functional

# Fast test commands (without DB reset) - for rapid development
test-fast: ## Run all tests WITHOUT DB reset (faster)
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit

ti-fast: ## Run integration tests WITHOUT DB reset
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --testsuite=Integration

tf-fast: ## Run functional tests WITHOUT DB reset
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --testsuite=Functional

coverage: ## Generate code coverage report (HTML)
	$(EXEC_PHP) -d pcov.enabled=1 -d pcov.directory=src vendor/bin/phpunit --coverage-html=var/coverage
	@echo ""
	@echo "✅ Coverage report generated: var/coverage/index.html"
	@echo "💡 See .claude/docs/COVERAGE_STRATEGY.md for coverage philosophy"

coverage-text: ## Show code coverage summary in terminal
	$(EXEC_PHP) -d pcov.enabled=1 -d pcov.directory=src vendor/bin/phpunit --coverage-text

coverage-summary: ## Show focused coverage summary (business logic only)
	@./scripts/coverage-summary.sh

# Run all test suites sequentially with single DB setup (optimal)
test-all: db-test ## Run all test suites with single DB setup (optimal)
	@echo "Running Unit tests..."
	@$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --testsuite=Unit
	@echo "\nRunning Integration tests..."
	@$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --testsuite=Integration
	@echo "\nRunning Functional tests..."
	@$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --testsuite=Functional
	@echo "\n✅ All test suites passed!"

# Coverage reports
test-coverage: ## Run tests with HTML coverage report
	$(EXEC_PHP) -d xdebug.mode=coverage bin/phpunit --coverage-html=var/coverage

test-coverage-text: ## Run tests with text coverage report
	$(EXEC_PHP) -d xdebug.mode=coverage bin/phpunit --coverage-text

# Filtered test execution
test-filter: ## Run tests matching a filter (usage: make test-filter FILTER=UserTest)
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --filter=$(FILTER)

test-group: ## Run tests by group (usage: make test-group GROUP=slow)
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --group=$(GROUP)

test-exclude-group: ## Exclude test group (usage: make test-exclude-group GROUP=slow)
	$(EXEC_PHP) -d xdebug.mode=off bin/phpunit --exclude-group=$(GROUP)

# CI/CD optimized command
test-ci: db-test ## Run all tests optimized for CI/CD
	$(EXEC_PHP) -d memory_limit=-1 -d xdebug.mode=off bin/phpunit --do-not-cache-result

.PHONY: test tu ti tf test-fast ti-fast tf-fast test-all test-coverage test-coverage-text test-filter test-group test-exclude-group test-ci

##
## Quality assurance
## -----------------
##

qa: ## Run all QA tests
qa: cs-fix stan

stan: ## Run static analysis
	@echo "Generating test environment container for PHPStan..."
	@$(SYMFONY) cache:warmup --env=test --no-debug --quiet
	$(EXEC_PHP) vendor/bin/phpstan analyse

stan-baseline: ## Generate PHPStan baseline
	@echo "Generating test environment container for PHPStan..."
	@$(SYMFONY) cache:warmup --env=test --no-debug --quiet
	$(EXEC_PHP) vendor/bin/phpstan analyse --generate-baseline

cs: ## Check coding standards
	$(DOCKER_EXEC) -u www-data -e PHP_CS_FIXER_IGNORE_ENV=1 app php -d memory_limit=-1 vendor/bin/php-cs-fixer fix --dry-run --using-cache=no --verbose --diff

cs-fix: ## Fix coding standards
	$(DOCKER_EXEC) -u www-data -e PHP_CS_FIXER_IGNORE_ENV=1 app php -d memory_limit=-1 vendor/bin/php-cs-fixer fix

.PHONY: qa stan stan-baseline cs cs-fix

# rules based on files
docker-compose.override.yml: docker-compose.override.yml.dist
ifeq ($(shell test -f docker-compose.override.yml && echo -n yes),yes)
	@echo "Your docker-compose.override.yml already exists."
else
	cp -n docker-compose.override.yml.dist docker-compose.override.yml
endif

vendor: composer.json composer.lock
	$(COMPOSER) install

.PHONY: vendor

.DEFAULT_GOAL := help
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help
