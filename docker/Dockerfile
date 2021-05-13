FROM php:8.0.5-cli-alpine

COPY --from=mlocati/php-extension-installer:1.2.25 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
      xdebug && \
      rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer:2.0.13 /usr/bin/composer /usr/local/bin/

COPY ../ /app

WORKDIR /app
