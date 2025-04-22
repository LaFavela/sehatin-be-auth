# PHP 8.2 CLI for Octane (no Apache)
FROM php:8.2-cli

LABEL maintainer="Dimas"
LABEL description="Laravel Octane with MongoDB and RabbitMQ"

# Set working directory
WORKDIR /var/www/html

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    unzip \
    git \
    curl \
    libzip-dev \
    librabbitmq-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mbstring pdo pdo_mysql xml bcmath zip pcntl sockets

# PHP extensions via PECL
RUN pecl install mongodb amqp swoole \
    && docker-php-ext-enable mongodb amqp swoole

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy app files
COPY . /var/www/html

# Mark the directory as safe
RUN git config --global --add safe.directory /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Install Laravel dependencies
RUN composer install --optimize-autoloader --no-dev

# Octane setup (if not done yet)
RUN php artisan config:clear \
    && php artisan config:cache \
    && php artisan route:cache

# Expose Octane port
EXPOSE 8000

# Start Laravel Octane using Swoole
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000"]
