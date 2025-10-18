# Resmi PHP Apache imajını temel alarak başlıyoruz.
FROM php:8.2-apache

# Gerekli sistem kütüphanelerini ve PHP eklentilerini kuruyoruz.
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

# 1. Tüm proje dosyalarının sahibini Apache kullanıcısı yap.
RUN chown -R www-data:www-data /var/www/html

# 2. Veritabanının bulunduğu 'includes' klasörüne tam yazma izni (777) ver.
# Bu, "readonly database" hatasını kesin olarak çözer.
RUN chmod -R 777 /var/www/html/includes

# Apache'nin mod_rewrite modülünü aktif hale getiriyoruz.
RUN a2enmod rewrite

# Docker container'ı dış dünyaya açmak için 80 numaralı portu kullanacağını belirtiyoruz.
EXPOSE 80