# Absolute APP_CONTAINER path
dir=${CURDIR}
pname=${APP_NAME}
project=-p $(pname)
app_pool_cache_dir=$(dir)/var/app_pool_cache
DOCKER_EXEC=docker exec
DOCKER_COMPOSE=docker compose
CONSOLE=php bin/console
APP_CONTAINER=php-fpm
ASYNC_MAIN_CONTAINER=async_main_consumer

service=symfony:latest
symfony_user=-u www-data
openssl_bin:=$(shell which openssl)
interactive:=$(shell [ -t 0 ] && echo 1)

ifneq ($(interactive),1)
  optionT=-T
endif

ifeq ($(GITLAB_CI),1)
  # Determine additional params for phpunit in order to generate coverage badge on GitLabCI side
  phpunitOptions=--coverage-text --colors=never
endif

ifndef APP_ENV
  include .env
  # Determine if .env.local file exist
  ifneq ("$(wildcard .env.local)","")
    include .env.local
  endif
endif

build: up composer migration about

up:
	@$(DOCKER_COMPOSE) -f '.docker/docker-compose/docker-compose.yml' up -d --build --remove-orphans
production:
	crontab -r
	cp ../.env.prod ./.env
	rm -rf var/cahce/prod
	@$(DOCKER_COMPOSE) \
	    -f '.docker/docker-compose/docker-compose.yml' \
	    -f '.docker/docker-compose/docker-compose.prod.yml' \
	    up -d --build
	$(DOCKER_EXEC) -t $(APP_CONTAINER) bash -c 'COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader'
	$(DOCKER_EXEC)c -t $(APP_CONTAINER) bash -c 'bin/console doctrine:migrations:migrate --no-interaction'
	cat .docker/docker-compose/crontab | crontab -
	$(DOCKER_EXEC) $(APP_CONTAINER) mkdir -p /APP_CONTAINER/var/sessions/prod
	$(DOCKER_EXEC) $(APP_CONTAINER) mkdir -p /APP_CONTAINER/var/sessions/dev
	$(DOCKER_EXEC) $(APP_CONTAINER) chmod -R 777 /APP_CONTAINER/var/sessions/
stop:
	@$(DOCKER_COMPOSE) -f '.docker/docker-compose/docker-compose.yml' down
ps:
	@$(DOCKER_COMPOSE) -f '.docker/docker-compose/docker-compose.yml' ps
ls:
	@docker ps -a --format 'table {{ .Names }}\t\t{{ .Status }}\t{{ .Size }}'
composer:
	@$(DOCKER_EXEC) -t $(APP_CONTAINER) bash -c \
		"COMPOSER_MEMORY_LIMIT=-1 composer i --ansi --no-interaction --no-progress --prefer-dist"
migration:
	@$(DOCKER_EXEC) -t $(APP_CONTAINER) bash -c '$(CONSOLE) doctrine:migrations:migrate --no-interaction'
ssh:
	@$(DOCKER_EXEC) -it $(APP_CONTAINER) bash -c 'fish && php -v'
add-user:
	@$(DOCKER_EXEC) -t $(APP_CONTAINER) bash -c '$(CONSOLE) fos:user:create dev address@mail.org dev --super-admin'
about:
	@$(DOCKER_EXEC) -t $(APP_CONTAINER) bash -c '$(CONSOLE) about'
	@echo "\033[32m\nCOMPLETED\033[39m"
worker-log:
	@$(DOCKER_COMPOSE) -f '.docker/docker-compose/docker-compose.cli.yml' logs -f
worker-restart:
	@$(DOCKER_COMPOSE) -f '.docker/docker-compose/docker-compose.cli.yml' exec $(ASYNC_MAIN_CONTAINER) $(CONSOLE) c:c
	@$(DOCKER_COMPOSE) -f '.docker/docker-compose/docker-compose.cli.yml' restart
# Stops all containers
stop-all:
	@printf "\033[32;49m\n - Stopping containers: \n"
	@docker stop $$(docker ps -q -a)
	@printf "\n \033[39m\n"
remove-all:
	@printf "\033[32;49m\n - Removing containers: \n"
	@docker rm -f $$(docker ps -q -a)
	@printf "\n \033[39m\n"
clear-all:
	@printf "\033[32;49m\n - Clearing...\n"
	@docker system prune -af
	@docker rmi -f $$(docker images -q)
	@printf "\n \033[39m\n"
make-up:
	@make build --warn-undefined-variables --trace --debug=basic

rebuild: stop-all remove-all make-up
hard-rebuild: stop-all remove-all clear-all make-up
