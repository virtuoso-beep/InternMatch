FROM php:8.4-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libzip-dev unzip \
	&& docker-php-ext-install pdo_mysql zip \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

COPY . .
RUN composer install --no-interaction --prefer-dist --no-progress
