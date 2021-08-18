build: common up ps composer migration

up:
	docker-compose \
	    -f .infrastructure/docker-compose/docker-compose.yml \
	    -f .infrastructure/docker-compose/docker-compose.dev.yml \
	    -f .infrastructure/docker-compose/docker-compose.cli.yml \
	    up -d --build

prod:
	crontab -r
	cp ../.env.prod ./.env
	rm -rf var/cahce/prod
	docker-compose \
	    -f .infrastructure/docker-compose/docker-compose.yml \
	    -f .infrastructure/docker-compose/docker-compose.prod.web.yml \
	    -f .infrastructure/docker-compose/docker-compose.cli.yml \
	    up -d --build
	docker exec -t php-fpm bash -c 'COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader'
	docker exec -t php-fpm bash -c 'bin/console doctrine:migrations:migrate --no-interaction'
	cat .infrastructure/docker-compose/crontab | crontab -
	docker exec php-fpm mkdir -p /application/var/sessions/prod
	docker exec php-fpm mkdir -p /application/var/sessions/dev
	docker exec php-fpm chmod -R 777 /application/var/sessions/

common:
	docker-compose -f .infrastructure/docker-compose/docker-compose.common.yml up -d --build

down:
	docker-compose -f .infrastructure/docker-compose/docker-compose.yml -f .infrastructure/docker-compose/docker-compose.dev.yml stop

ps:
	docker-compose -f .infrastructure/docker-compose/docker-compose.yml -f .infrastructure/docker-compose/docker-compose.dev.yml ps

composer:
	docker exec -t php-fpm bash -c 'COMPOSER_MEMORY_LIMIT=-1 composer install'

migration:
	docker exec -t php-fpm bash -c 'bin/console doctrine:migrations:migrate --no-interaction'

worker-log:
	docker-compose -f .infrastructure/docker-compose/docker-compose.cli.yml logs -f

worker-restart:
	docker-compose -f .infrastructure/docker-compose/docker-compose.cli.yml exec async_main_consumer bin/console cache:clear
	docker-compose -f .infrastructure/docker-compose/docker-compose.cli.yml exec async_position_consumer bin/console cache:clear
	docker-compose -f .infrastructure/docker-compose/docker-compose.cli.yml restart

php:
	docker exec -it php-fpm bash