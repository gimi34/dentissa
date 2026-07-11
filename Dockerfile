FROM php:8.4-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www

# Copiar primero solo composer.json y composer.lock
COPY composer.json composer.lock ./

# Instalar dependencias sin scripts para evitar errores
RUN composer install \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs

# Copiar el resto del proyecto
COPY . .

# Generar autoloader optimizado
RUN composer dump-autoload --optimize --ignore-platform-reqs

# Permisos para Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]