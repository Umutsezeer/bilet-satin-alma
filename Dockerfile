# Resmi PHP Apache imajını temel alarak başlıyoruz.
FROM php:8.2-apache

# Gerekli sistem kütüphanelerini kuruyoruz ve PHP eklentilerini yüklüyoruz.
# apt-get update: Paket listesini günceller.
# apt-get install: Gerekli kütüphaneleri kurar (libsqlite3-dev ve gd için kütüphaneler).
# docker-php-ext-install: PHP eklentilerini derleyip kurar.
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite gd

# Apache'nin web sitesi dosyalarını sunacağı ana klasörü ayarlıyoruz.
WORKDIR /var/www/html

# Projemizdeki tüm dosyaları Docker'ın içindeki bu çalışma klasörüne kopyalıyoruz.
COPY . .

# Apache'nin mod_rewrite modülünü aktif hale getiriyoruz.
RUN a2enmod rewrite

# Docker container'ı dış dünyaya açmak için 80 numaralı portu kullanacağını belirtiyoruz.
EXPOSE 80