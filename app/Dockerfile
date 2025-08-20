FROM php:8.2-apache

WORKDIR /var/www/app

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN apt-get update \
&& apt-get install -qq -y --no-install-recommends \
cron \
wget \
nano \
locales \
coreutils \
apt-utils \
git \
libicu-dev \
g++ \
libpng-dev \
libxml2-dev \
libzip-dev \
libonig-dev \
libxslt-dev \
libcurl4-openssl-dev \
libgd-dev \
libc-client-dev \
libssl-dev \
libkrb5-dev

RUN docker-php-ext-install curl pdo pdo_mysql mysqli gd opcache intl zip calendar dom mbstring gd xsl && a2enmod rewrite

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen && \
locale-gen

# install curl and git
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
mv composer.phar /usr/local/bin/composer

# Install Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Configure and install extensions
RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo pdo_mysql mysqli gd opcache intl zip calendar dom mbstring gd xsl && a2enmod rewrite
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
&& docker-php-ext-install imap
RUN pecl install apcu && docker-php-ext-enable apcu

# Use the mlocati installer for additional extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
install-php-extensions amqp

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN apt-get update && apt-get install -y \
software-properties-common \
npm

# Install Node.js and npm
RUN npm install -g n
RUN n 20.17.0  # Install a compatible version of Node.js
RUN npm install -g npm@11.2.0
#RUN npm install npm@latest -g && \
#npm install n -g && \
#n latest

# SSL : Génération du certificat et configuration Apache
EXPOSE 443
RUN apt-get update && apt-get install -y openssl
RUN mkdir -p /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/apache.key \
    -out /etc/apache2/ssl/apache.crt \
    -subj "/C=FR/ST=France/L=Paris/O=Dev/OU=Dev/CN=localhost"
RUN a2enmod ssl
COPY ./config/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite default-ssl

# Configuration de Xdebug
COPY ./config/xdebug.ini /etc/php/8.2/apache2/conf.d/30-xdebug.ini
COPY ./config/xdebug.ini /etc/php/8.2/cli/conf.d/30-xdebug.ini

RUN chown -R root:root /var/www/app

RUN chmod -R 755 /var/www/app
RUN chmod +x /var/www/app/
COPY . /var/www/app

# Ensure the composer.json file exists
RUN composer install

EXPOSE 8000

# CMD ["symfony", "serve","--no-tls"]

CMD ["apache2-foreground"]
# ENTRYPOINT ["/bin/bash", "/var/www/app/run.sh"]