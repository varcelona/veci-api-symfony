#!/bin/bash

include .docker.env

help: ## Show this help message
	@echo 'usage: make [target]'
	@echo
	@echo 'targets:'
	@egrep '^(.+)\:\ ##\ (.+)' ${MAKEFILE_LIST} | column -t -c 2 -s ':#'

start: ## Start the containers
	U_ID=${UID} docker compose -f docker-compose.${APP_ENV}.yml up -d

stop: ## Stop the containers
	U_ID=${UID} docker compose -f docker-compose.${APP_ENV}.yml stop

restart: ## Restart the containers
	$(MAKE) stop && $(MAKE) start

build: ## Rebuilds all the containers
	U_ID=${UID} docker compose -f docker-compose.${APP_ENV}.yml build --no-cache

build-up: ## Rebuilds all the containers
	U_ID=${UID} docker compose -f docker-compose.${APP_ENV}.yml up -d --build

prepare: ## Runs backend commands
	$(MAKE) composer-install

make migrate: ## Runs composer commands
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console make:migration
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} php bin/console doctrine:migrations:migrate

logs: ## Show Symfony logs in real time
	tail -f var/log/${APP_ENV}.log

composer-install: ## Installs composer dependencies
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} composer install --no-interaction
# End backend commands

schema-update:
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console cache:pool:clear doctrine.system_cache_pool
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console doctrine:schema:update --force

sh: ## bash into the be container
	U_ID=${UID} docker exec -it --user ${UID} ${DOCKER_BE} sh

phpstan-run: ## Run PHPSTAN test
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} vendor/bin/phpstan analyse > ./symfony/phpstan-results.txt 2>&1

phpstan-clear: ## Clear cache from PHPSTAN
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} vendor/bin/phpstan clear-result-cache

cc: ## all cache clear
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console doctrine:cache:clear-metadata
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console cache:pool:clear --all
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console cache:clear --env=${APP_ENV} --no-warmup
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console cache:warmup --env=${APP_ENV}

assets-install: ## Install assets
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console importmap:install
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console assets:install

assets-compile: ## Compile assets
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console asset-map:compile
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console cache:clear

deploy: ## Deploy environment
	$(MAKE) cc

	## Install Composer dependencies
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} composer install --no-dev --optimize-autoloader --no-progress --no-scripts --prefer-dist


	## Install and compile asset-map
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console importmap:install || true
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} bin/console asset-map:compile || true

	## Generate an optimized env file
	U_ID=${UID} docker exec --user ${UID} ${DOCKER_BE} composer dump-env ${APP_ENV}

	$(MAKE) cc

	## Install and compile assets
	$(MAKE) assets-install && $(MAKE) assets-compile