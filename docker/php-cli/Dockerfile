FROM php:8.4-cli

COPY ./php.ini "${PHP_INI_DIR}/conf.d/docker-php-config.ini"

RUN apt-get update && apt-get install -y \
    gnupg \
    g++ \
    procps \
    openssl \
    curl \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libfreetype6-dev \
    libpng-dev \
    libjpeg-dev \
    libicu-dev  \
    libonig-dev \
    libxslt1-dev \
    acl

RUN docker-php-ext-configure gd --with-jpeg --with-freetype

RUN docker-php-ext-install pdo pdo_mysql zip xsl gd intl opcache exif mbstring sockets
RUN docker-php-ext-install bcmath pcntl

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY ./xdebug.ini "${PHP_INI_DIR}/conf.d"

# Install Blackfire.
RUN mkdir -p /tmp/blackfire \
    && architecture=$(uname -m) \
    && curl -A "Docker" -L https://blackfire.io/api/v1/releases/cli/linux/$architecture | tar zxp -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire /usr/bin/blackfire \
    && rm -Rf /tmp/blackfire

RUN blackfire php:install

# Download Caddy to be able to format config file.
RUN curl -L -o "/tmp/caddy.tar.gz" https://github.com/caddyserver/caddy/releases/download/v2.10.2/caddy_2.10.2_linux_amd64.tar.gz
RUN tar -xzf /tmp/caddy.tar.gz -C /usr/bin caddy
RUN rm -f /tmp/caddy.tar.gz
RUN chmod +x /usr/bin/caddy

# Install node and npm.
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash
RUN apt-get install -y nodejs

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer