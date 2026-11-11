# Laravel + Railway Docker Deployment

FROM php:8.4-fpm-alpine AS builder

# Install build dependencies (PHP + Node.js)
RUN apk add --no-cache \
    curl \
    nodejs \
    npm \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    gd \
    zip \
    bcmath \
    intl \
    xml \
    fileinfo \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# Copy package files for Node
COPY package.json package-lock.json* ./
RUN npm ci

# Copy all project files
COPY . .

# Always build assets on Railway (Node version is stable in container)
# VITE_REVERB_* come from Railway environment variables at build time
RUN npm run build:prod \
    && composer dump-autoload --optimize \
    && rm -rf node_modules

# Verify build output
RUN ls -la public/build/ && test -f public/build/manifest.json

# Production stage
FROM php:8.4-fpm-alpine

# Install runtime dependencies + build deps (headers), compile extensions, then drop build deps
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng \
    libzip \
    oniguruma \
    icu \
    libxml2 \
    && apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    gd \
    zip \
    bcmath \
    intl \
    xml \
    fileinfo \
    opcache \
    pcntl \
    && apk del .build-deps

WORKDIR /var/www/html

# Copy built application from builder stage
COPY --from=builder /var/www/html /var/www/html

# Create necessary directories and set permissions
RUN mkdir -p /var/log/supervisor /run/nginx /tmp/nginx /var/lib/nginx/tmp \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/log /run/nginx /tmp/nginx /var/lib/nginx \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 8080

CMD ["/entrypoint.sh"]