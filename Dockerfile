# ================================================================
# Juicios Evaluativos — Imagen de producción (PHP + Apache)
# ================================================================
FROM php:8.3-apache

# --- Dependencias del sistema y extensiones PHP -----------------
# pdo_mysql -> conexión a MariaDB/MySQL
# mbstring  -> mb_strtolower() usado en los parsers de reportes
# opcache   -> rendimiento en producción
RUN apt-get update \
 && apt-get install -y --no-install-recommends libonig-dev curl \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring opcache \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# --- Configuración de Apache ------------------------------------
RUN a2enmod rewrite headers expires
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache-security.conf /etc/apache2/conf-available/zz-security.conf
# configtest aborta el build si la configuracion de Apache no es valida,
# en vez de dejar que el contenedor arranque y muera en el VPS.
RUN a2enconf zz-security && apache2ctl configtest

# --- Configuración de PHP ---------------------------------------
COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini

# --- Código de la aplicación ------------------------------------
WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html

# Carpeta usada por api/save_debug.php (excluida del repo por .gitignore)
RUN mkdir -p /var/www/html/scratch \
 && chown -R www-data:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 755 {} + \
 && find /var/www/html -type f -exec chmod 644 {} +

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
  CMD curl -fsS http://localhost/health.php || exit 1

CMD ["apache2-foreground"]
