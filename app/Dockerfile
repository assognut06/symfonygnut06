FROM php:8.2-apache

WORKDIR /var/www/app

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Dépendances système + nettoyage du cache apt pour alléger l'image
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        apt-utils \
        ca-certificates \
        coreutils \
        cron \
        curl \
        g++ \
        git \
        libcurl4-openssl-dev \
        libgd-dev \
        libicu-dev \
        libkrb5-dev \
        libonig-dev \
        libpng-dev \
        libssl-dev \
        libwebp-dev \
        libxml2-dev \
        libxslt-dev \
        libzip-dev \
        locales \
        nano \
        openssl \
        wget \
    && rm -rf /var/lib/apt/lists/*

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen \
    && locale-gen

# Extensions PHP
RUN docker-php-ext-configure gd --with-webp --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        calendar \
        curl \
        dom \
        gd \
        intl \
        mbstring \
        mysqli \
        opcache \
        pdo \
        pdo_mysql \
        xsl \
        zip

RUN pecl install apcu xdebug \
    && docker-php-ext-enable apcu xdebug

# Extension AMQP via installateur mlocati
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions amqp

RUN a2enmod rewrite ssl headers

# Composer depuis l'image officielle (évite le téléchargement non TLS)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Node.js 20 + npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g npm@11.2.0 \
    && rm -rf /var/lib/apt/lists/*

# SSL : génération du certificat et configuration Apache
RUN mkdir -p /etc/apache2/ssl \
    && openssl req -x509 -nodes -days 365 -newkey rsa:4096 \
        -keyout /etc/apache2/ssl/apache.key \
        -out /etc/apache2/ssl/apache.crt \
        -subj "/C=FR/ST=PACA/L=Nice/O=GNUT06/OU=Association/CN=localhost" \
        -addext "subjectAltName=DNS:localhost,DNS:*.localhost,IP:127.0.0.1"

COPY ./config/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite default-ssl

# Configuration Xdebug pour l'image officielle php
COPY ./config/xdebug.ini /usr/local/etc/php/conf.d/30-xdebug.ini

COPY . /var/www/app

RUN addgroup --gid 1000 user \
    && usermod -aG user www-data \
    && chmod -R 755 /var/www/app

RUN composer install --no-scripts --no-interaction --prefer-dist

EXPOSE 80 443

CMD ["apache2-foreground"]