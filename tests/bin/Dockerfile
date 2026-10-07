ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-cli

# 1. Install system dependencies (added libmemcached-dev and zlib1g-dev)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libgmp-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libxml2-dev \
    libmemcached-dev \
    zlib1g-dev

# 2. Install native PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install gmp intl mbstring pdo pdo_mysql

# 3. Download and install third-party extensions via PECL
RUN pecl install memcached redis \
    && docker-php-ext-enable gmp intl mbstring memcached redis

# 4. Clean cache to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY . /usr/src/springy
WORKDIR /usr/src/springy

RUN composer install --optimize-autoloader

CMD [ "composer", "run", "test" ]
