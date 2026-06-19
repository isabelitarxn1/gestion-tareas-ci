FROM php:8.2-apache

# Instalar extensión mysqli para conectar con MySQL
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copiar archivos de la aplicación al directorio raíz de Apache
COPY . /var/www/html/

# Dar permisos correctos
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80