# Spec 030 (D4): imágenes de producción, con el código dentro y sin
# dependencias de desarrollo. Versiones fijadas e iguales a las de local
# (CA10, RS7.d). Se construye desde la raíz del clon:
#   docker build -f docker/prod/Dockerfile --target app .
#   docker build -f docker/prod/Dockerfile --target web .

# ── app: php-fpm con la aplicación ──────────────────────────────────────────
FROM php:8.4.25-fpm AS app

# Paquetes del sistema al día: aplica las correcciones de seguridad ya
# publicadas sobre la imagen base fijada (plan D14, P6). Lo que no tiene
# corrección está en la excepción EX2 de docs/security.md.
RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.10.2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Dependencias sin las de desarrollo (antes que el código, para la caché).
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# El código. .dockerignore deja fuera secretos, .git, vendor, el volcado,
# storage, tests y documentación (RS7.c, TM6).
COPY . .

# Sin cachés de bootstrap de un equipo de desarrollo (citarían paquetes de
# desarrollo); el autoloader y el descubrimiento de paquetes, ya sin ellos.
RUN rm -f bootstrap/cache/*.php \
    && composer dump-autoload --optimize --no-dev \
    && php artisan package:discover \
    && rm /usr/bin/composer \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
       storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]

# ── web: nginx con los archivos públicos ────────────────────────────────────
FROM nginx:1.30.5-alpine AS web

# Paquetes del sistema al día (plan D14, P6).
RUN apk upgrade --no-cache

COPY docker/nginx/dentissa.prod.conf /etc/nginx/conf.d/default.conf
# Cabeceras de seguridad que incluye la configuración (spec 031, D1).
COPY docker/nginx/seguridad /etc/nginx/snippets/dentissa
COPY --from=app /var/www/public /var/www/public
