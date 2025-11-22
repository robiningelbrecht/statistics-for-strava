FROM dunglas/frankenphp:1-php8.5-alpine

WORKDIR /var/www

# hadolint ignore=DL3018
RUN apk add --no-cache \
  bash \
  curl \
  file \
  tzdata

RUN set -eux; \
	install-php-extensions \
        bcmath \
        ctype \
        curl \
        dom \
        fileinfo \
        gd \
        intl \
        mbstring \
        opcache \
        pdo \
        pdo_sqlite \
        phar \
        session \
        simplexml \
        tokenizer \
        xml \
        xmlreader \
        xmlwriter \
        pcntl;

COPY docker/app/config/php.ini ${PHP_INI_DIR}/php.ini
COPY docker/app/config/Caddyfile /etc/frankenphp/Caddyfile

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Add application
COPY . /var/www/
RUN touch /var/www/.env
RUN rm -Rf docker
RUN mkdir -p /var/www/var/cache/dev
RUN mkdir -p /var/www/var/log

CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile" ]