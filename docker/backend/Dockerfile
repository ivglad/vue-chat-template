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
    make

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

RUN chown -R www-data:www-data /var/www/backend/storage /var/www/backend/bootstrap/cache
RUN chmod -R 775 /var/www/backend/storage /var/www/backend/bootstrap/cache

COPY docker/backend/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/backend/init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

EXPOSE 9000

CMD ["/usr/local/bin/init.sh"] 