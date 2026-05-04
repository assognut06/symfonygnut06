FROM ubuntu/apache2:latest
WORKDIR /var/www/app

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
RUN apt-get update \
&& apt-get install -qq -y --no-install-recommends \
cron \
wget \
nano \
locales \
coreutils \
apt-utils \
git \
openssl \
npm \
nodejs \
curl \
ca-certificates


RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen && \
locale-gen


# Install Node.js and npm
RUN npm install -g n && \
n 20.17.0  && \
# Install a compatible version of Node.js
npm install -g npm@11.2.0
#RUN npm install npm@latest -g && \
#npm install n -g && \
#n latest


# SSL : Génération du certificat et configuration Apache
EXPOSE 443
RUN mkdir -p /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:4096 \
    -keyout /etc/apache2/ssl/apache.key \
    -out /etc/apache2/ssl/apache.crt \
    -subj "/C=FR/ST=PACA/L=Nice/O=GNUT06/OU=Association/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,DNS:*.localhost,IP:127.0.0.1" && \
    a2enmod ssl headers
COPY ./config/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite default-ssl && a2enmod proxy proxy_http proxy_fcgi && a2enmod rewrite


RUN echo "Listen 8080" >> /etc/apache2/ports.conf

EXPOSE 8000
EXPOSE 8080
# CMD ["symfony", "serve","--no-tls"]

CMD ["apache2-foreground"]
#ENTRYPOINT ["/bin/bash", "/var/www/app/run.sh"]
