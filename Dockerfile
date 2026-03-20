FROM php:8.3-apache

RUN docker-php-ext-install mysqli
RUN a2enmod rewrite

COPY src/ /var/www/html/
