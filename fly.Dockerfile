# Fly.io custom Dockerfile for pinjam-in
FROM php:8.4-fpm-alpine

# Install dependencies (PHP + Node.js)
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    nodejs \
    npm \
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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# Install Node + build assets
COPY package.json package-lock.json* ./
RUN npm ci

# Copy app
COPY . .

# Build assets + optimize
RUN npm run build:prod \
    && composer dump-autoload --optimize \
    && rm -rf node_modules

# Permissions
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
