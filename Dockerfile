#==============================================================================
# Portal API - Production Dockerfile
# PHP 8.4 FPM + SQL Server Support
#==============================================================================

FROM php:8.4-fpm-alpine AS base

# Metadata
LABEL maintainer="Portal API Team"
LABEL description="YUDO Portal API - Laravel Application"

# Environment variables
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies
RUN apk add --no-cache \
    # Build dependencies
    $PHPIZE_DEPS \
    # Required packages
    curl \
    git \
    unzip \
    zip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    # SQL Server ODBC dependencies
    unixodbc-dev \
    gnupg

#------------------------------------------------------------------------------
# Install Microsoft ODBC Driver for SQL Server
#------------------------------------------------------------------------------
RUN curl -O https://download.microsoft.com/download/3/5/5/355d7943-a338-41a7-858d-53b259ea33f5/msodbcsql18_18.3.3.1-1_amd64.apk \
    && curl -O https://download.microsoft.com/download/3/5/5/355d7943-a338-41a7-858d-53b259ea33f5/mssql-tools18_18.3.1.1-1_amd64.apk \
    && apk add --allow-untrusted msodbcsql18_18.3.3.1-1_amd64.apk \
    && apk add --allow-untrusted mssql-tools18_18.3.1.1-1_amd64.apk \
    && rm -f msodbcsql18_18.3.3.1-1_amd64.apk mssql-tools18_18.3.1.1-1_amd64.apk

#------------------------------------------------------------------------------
# Install PHP Extensions
#------------------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        mbstring \
        zip \
        intl \
        gd \
        bcmath \
        opcache \
        pcntl

# Install SQL Server PHP extensions
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

#------------------------------------------------------------------------------
# Install Composer
#------------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

#------------------------------------------------------------------------------
# PHP Configuration
#------------------------------------------------------------------------------
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

#------------------------------------------------------------------------------
# Application Setup
#------------------------------------------------------------------------------
WORKDIR /var/www/html

# Copy composer files first (for better caching)
COPY composer.json composer.lock ./

# Install dependencies (no dev for production)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Generate optimized autoload
RUN composer dump-autoload --optimize --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

#------------------------------------------------------------------------------
# Production Optimizations
#------------------------------------------------------------------------------
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# Run PHP-FPM
CMD ["php-fpm"]

#==============================================================================
# Development Stage
#==============================================================================
FROM base AS development

ENV APP_ENV=local
ENV APP_DEBUG=true

# Install dev dependencies
RUN composer install --prefer-dist

# Install Xdebug for development
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Override PHP config for development
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/99-dev.ini

CMD ["php-fpm"]
