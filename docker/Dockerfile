FROM php:8.0.3-cli-alpine

COPY --from=mlocati/php-extension-installer:1.2.23 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
      xdebug

COPY --from=composer:2.0.12 /usr/bin/composer /usr/local/bin/

COPY ../ /app

WORKDIR /app
