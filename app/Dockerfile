FROM php:8.2-fpm

WORKDIR /var/www/app


# Args to pass host UID/GID (default to 33 if not provided)
ARG USER_UID=33
ARG USER_GID=33



RUN apt-get update \
&& apt-get install -qq -y --no-install-recommends \
cron \
wget \
nano \
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
curl

# Modify www-data group to use host GID
RUN groupmod -g ${USER_GID} www-data
 
# Modify www-data user to use host UID and GID
RUN usermod -u ${USER_UID} -g ${USER_GID} www-data



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
docker-php-ext-install curl pdo pdo_mysql mysqli gd opcache intl zip calendar dom mbstring xsl && \
pecl install apcu && docker-php-ext-enable apcu

# Use the mlocati installer for additional extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
install-php-extensions amqp

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configuration de Xdebug
COPY ./config/xdebug.ini /etc/php/8.2/apache2/conf.d/30-xdebug.ini
COPY ./config/xdebug.ini /etc/php/8.2/cli/conf.d/30-xdebug.ini

RUN chown -R root:root /var/www/app && \
chmod -R 755 /var/www/app && \
chmod +x /var/www/app/
COPY . /var/www/app

RUN addgroup --gid 1000 user && usermod -aG user www-data

# Ensure the composer.json file exists
RUN composer install --no-scripts
