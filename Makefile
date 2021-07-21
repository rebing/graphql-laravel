docker-phpstan:
	docker-compose -f docker-compose-dev.yml run --rm php sh -c "php -v ; composer phpstan"
docker-phpstan-baseline:
	docker-compose -f docker-compose-dev.yml run --rm php sh -c "php -v ; composer phpstan-baseline"
