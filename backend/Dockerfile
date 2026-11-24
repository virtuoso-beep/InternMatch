# Two targets. docker-compose.yml builds "development" (php artisan serve, used locally);
# docker-compose.production.yml builds "production" (Apache with mod_php and PHP's production settings).

FROM php:8.4-cli AS development

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libzip-dev libicu-dev libpng-dev unzip \
	&& docker-php-ext-install pdo_mysql zip intl gd \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY imports.ini /usr/local/etc/php/conf.d/internmatch-imports.ini

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-interaction --prefer-dist --no-progress


FROM php:8.4-apache AS production

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libzip-dev libicu-dev libpng-dev unzip \
	&& docker-php-ext-install pdo_mysql zip intl gd opcache \
	&& rm -rf /var/lib/apt/lists/* \
	&& a2enmod rewrite headers \
	&& cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Laravel's public/ is the web root; Apache listens on 8000 so Nginx and the frontend keep the same upstream.
RUN sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' /etc/apache2/sites-available/000-default.conf \
	&& sed -ri 's!<VirtualHost \*:80>!<VirtualHost *:8000>!' /etc/apache2/sites-available/000-default.conf \
	&& sed -ri 's!^Listen 80$!Listen 8000!' /etc/apache2/ports.conf \
	&& printf '%s\n' \
		'<Directory /var/www/html/public>' \
		'    AllowOverride All' \
		'    Require all granted' \
		'</Directory>' \
		'ServerName localhost' \
		'ServerTokens Prod' \
		'ServerSignature Off' \
		> /etc/apache2/conf-available/internmatch.conf \
	&& a2enconf internmatch

COPY imports.ini /usr/local/etc/php/conf.d/internmatch-imports.ini
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-internmatch-production.ini
COPY docker-entrypoint.production.sh /usr/local/bin/internmatch-production-entrypoint
RUN chmod +x /usr/local/bin/internmatch-production-entrypoint

WORKDIR /var/www/html
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache

CMD ["internmatch-production-entrypoint"]
