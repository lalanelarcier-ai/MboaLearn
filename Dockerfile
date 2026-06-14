FROM php:8.2-apache

# Installer les extensions PHP necessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Activer mod_rewrite pour Apache
RUN a2enmod rewrite

# Copier le projet dans le container
COPY . /var/www/html/MboaLearn/

# Permissions sur le dossier uploads
RUN mkdir -p /var/www/html/MboaLearn/uploads \
    && chown -R www-data:www-data /var/www/html/MboaLearn/ \
    && chmod -R 755 /var/www/html/MboaLearn/

# PHP config
RUN echo "upload_max_filesize = 64M" >> /usr/local/etc/php/php.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/php.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/php.ini

EXPOSE 80

CMD ["apache2-foreground"]
