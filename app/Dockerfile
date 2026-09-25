FROM php:8.3-apache

WORKDIR /var/www/app

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install Node.js 22.23.2 from NodeSource.
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
libwebp-dev \
libssl-dev \
libkrb5-dev \
openssl \
curl \
ca-certificates \
&& rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
apt-get update && \
apt-get install -qq -y --no-install-recommends nodejs=22.23.2-1nodesource1 && \
rm -rf /var/lib/apt/lists/*

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
RUN docker-php-ext-configure intl && \
# Configure GD with WebP support
docker-php-ext-configure gd --with-webp --with-jpeg && \
docker-php-ext-install curl pdo pdo_mysql mysqli gd opcache intl zip calendar dom mbstring xsl && a2enmod rewrite && \
pecl install apcu && docker-php-ext-enable apcu

# Use the mlocati installer for additional extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
install-php-extensions amqp

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug


# Install the requested npm version compatible with Node.js 22.23.2.
RUN npm install -g npm@11.12.1

# SSL: certificate generation and Apache configuration
RUN mkdir -p /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:4096 \
    -keyout /etc/apache2/ssl/apache.key \
    -out /etc/apache2/ssl/apache.crt \
    -subj "/C=FR/ST=PACA/L=Nice/O=GNUT06/OU=Association/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,DNS:*.localhost,IP:127.0.0.1" && \
    a2enmod ssl headers
COPY ./vhosts.conf /etc/apache2/sites-available/000-default.conf
COPY ./config/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite default-ssl

# Configuration of the HTTP VirtualHost on port 80
# Disable the default HTTP site (DocumentRoot /var/www/html).
RUN a2dissite 000-default.conf || true
COPY ./vhosts.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default

# Configuration de Xdebug
COPY ./config/xdebug.ini /usr/local/etc/php/conf.d//xdebug.ini

RUN addgroup --gid 1000 user && usermod -aG user www-data
RUN chown -R www-data:user /var/www && \
chmod -R 755 /etc/apache2 && \
mkdir -p /var/www/app/var /var/www/app/public/uploads /var/www/app/public/media && \
chmod -R 775 /var/www/app/var /var/www/app/public/uploads /var/www/app/public/media && \
chmod +x /var/www/app/ && \
# The SSL private key must only be readable by root.
# Apache runs as root while loading it, before its workers drop to www-data.
chmod 700 /etc/apache2/ssl && \
chmod 600 /etc/apache2/ssl/apache.key && \
chmod 644 /etc/apache2/ssl/apache.crt && \
chown -R root:root /etc/apache2/ssl
COPY --chown=www-data:user . /var/www/app

# Gestion des permissions des dossiers montés (var/, public/uploads)
# Le script tourne au démarrage pour rendre ces dossiers inscriptibles par
# www-data, indépendamment de l'UID/GID du poste hôte.
COPY --chown=www-data:user ./docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www-data:user
# Ensure the composer.json file exists
RUN composer install --no-scripts
# RUN composer install
# Switch back to root: the entrypoint adjusts mounted-volume ownership and
# Apache starts as root before its workers drop to www-data.
USER root

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
