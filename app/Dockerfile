FROM php:8.2-apache

WORKDIR /var/www/app

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN apt-get update \
    && apt-get install -qq -y --no-install-recommends \
    cron \
    wget \
    nano \
    locales coreutils apt-utils git libicu-dev g++ libpng-dev libxml2-dev libzip-dev libonig-dev libxslt-dev

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

# install curl and git
RUN apt-get update && apt-get install -y \
    curl 

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer

# installe symfony cli 
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

RUN docker-php-ext-configure intl

RUN docker-php-ext-install pdo pdo_mysql mysqli gd opcache intl zip calendar dom mbstring gd xsl && a2enmod rewrite
RUN pecl install apcu && docker-php-ext-enable apcu

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions amqp
  

COPY . /var/www/app/

RUN chmod +x /var/www/app/

RUN apt-get update && apt-get install -y dos2unix
RUN dos2unix /var/www/app/run.sh

RUN chmod +x /var/www/app/run.sh

RUN chown -R www-data:www-data /var/www/app/
# Set environment variable for Composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Ensure the composer.json file exists
RUN if [ -f composer.json ]; then composer install; fi

RUN a2ensite 000-default.conf
    
RUN apt-get update && apt-get install -y \
    software-properties-common \
    npm

RUN npm install npm@latest -g && \
    npm install n -g && \
    n latest


ENTRYPOINT ["sh", "/var/www/app/run.sh"]

# CMD ["symfony", "serve"]