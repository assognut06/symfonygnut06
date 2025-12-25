FROM php:8.2-apache

WORKDIR /var/www/app

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Combine all apt-get commands and clean cache in one layer
RUN apt-get update && apt-get install -qq -y --no-install-recommends \
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
    libssl-dev \
    libkrb5-dev \
    curl \
    ca-certificates \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# Configure locales
RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

# Install PHP extensions in one layer (remove duplicates)
RUN docker-php-ext-configure intl && \
    docker-php-ext-install pdo pdo_mysql mysqli gd opcache intl zip calendar dom mbstring xsl && \
    a2enmod rewrite

# Install PECL extensions
RUN pecl install apcu xdebug && \
    docker-php-ext-enable apcu xdebug

# Use the mlocati installer for additional extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions amqp

# Install Composer
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
    mv composer.phar /usr/local/bin/composer

# Install Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Install Node.js 20.x (combine all Node.js installation steps)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@11.2.0 && \
    rm -rf /var/lib/apt/lists/*

# SSL configuration
RUN mkdir -p /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/apache.key \
    -out /etc/apache2/ssl/apache.crt \
    -subj "/C=FR/ST=France/L=Paris/O=Dev/OU=Dev/CN=localhost" && \
    a2enmod ssl

# Copy configuration files early (these rarely change)
COPY ./config/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
COPY ./config/xdebug.ini /etc/php/8.2/apache2/conf.d/30-xdebug.ini
COPY ./config/xdebug.ini /etc/php/8.2/cli/conf.d/30-xdebug.ini

RUN a2ensite default-ssl

# Copy composer files first for better layer caching
COPY composer.json composer.lock* /var/www/app/

# Copy the rest of the application
COPY . /var/www/app

# Copy and enable custom vhost configuration
COPY vhosts.conf /etc/apache2/sites-available/000-default.conf

# Install dependencies after copying all files
RUN composer install --prefer-dist --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/app && \
    chmod -R 755 /var/www/app

EXPOSE 8000 443

CMD ["apache2-foreground"]