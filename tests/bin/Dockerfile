ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-cli
RUN apt-get update && apt-get install -y git curl unzip libgmp-dev libicu-dev libonig-dev libpng-dev libxml2-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install gmp intl mbstring pdo pdo_mysql \
    && docker-php-ext-enable gmp intl mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY . /usr/src/springy
WORKDIR /usr/src/springy
RUN composer install --optimize-autoloader
CMD [ "php", "vendor/bin/phpunit" ]