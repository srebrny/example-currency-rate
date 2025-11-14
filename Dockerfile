FROM composer:2 AS composer

FROM prestashop/prestashop:9.0.1

# Copy Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libmemcached-dev \
    zlib1g-dev \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP Memcached extension
RUN pecl install memcached && docker-php-ext-enable memcached

# Verify installation
RUN php -m | grep memcached

# Ensure composer home directory exists
ENV COMPOSER_HOME=/tmp/composer
RUN mkdir -p $COMPOSER_HOME && chown www-data:www-data $COMPOSER_HOME
