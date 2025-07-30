FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    git \
    curl \
    nodejs \
    npm \
    libreoffice \
    supervisor \
    icu-dev \
    libffi-dev \
    autoconf \
    gcc \
    g++ \
    make \
    bash \
    expect

RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    xml \
    dom \
    zip \
    opcache \
    intl \
    exif \
    ffi

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/log/supervisor

WORKDIR /var/www/backend

COPY ./backend/composer.json ./backend/composer.lock ./
RUN composer install --optimize-autoloader --no-scripts --ignore-platform-reqs

COPY ./backend .

RUN npm install && npm run build

# Делаем скрипты исполняемыми (если они существуют)
RUN chmod +x /var/www/backend/fix-permissions.sh || echo "fix-permissions.sh not found, skipping"
RUN chmod +x /var/www/backend/check-permissions.sh || echo "check-permissions.sh not found, skipping"

# Устанавливаем базовые права доступа для Laravel
RUN chown -R www-data:www-data /var/www/backend/storage /var/www/backend/bootstrap/cache
RUN chmod -R 775 /var/www/backend/storage /var/www/backend/bootstrap/cache

# Install netcat for database health checks
RUN apk add --no-cache netcat-openbsd

# Copy configuration files
COPY docker/backend/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh

# Make entrypoint executable
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

# Use modern entrypoint pattern
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"] 