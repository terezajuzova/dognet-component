ARG phpversion=8

FROM php:${phpversion}-cli

ARG COMPOSER_FLAGS=--prefer-dist
ARG SYMFONY_REQUIRE=6.*

ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /code

RUN apt-get update && apt-get install -y \
        git \
        unzip \
   --no-install-recommends && rm -r /var/lib/apt/lists/*

COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

# To enable SYMFONY_REQUIRE
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
 && composer global config allow-plugins.symfony/flex true \
 && composer global require --no-progress --no-scripts --no-plugins symfony/flex

COPY composer.* ./
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader
COPY . .
RUN composer install $COMPOSER_FLAGS

RUN mkdir /data
COPY config.json /data

#CMD composer ci
#RUN chmod 764 run.sh
#CMD ./run.sh
CMD php src/run.php


