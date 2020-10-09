FROM debian:buster

ENV DEBIAN_FRONTEND=noninteractive

# Basics
RUN apt-get update && apt-get install -y wget lsb-release

# Install PHP and the rest
# Debian Buster only has php-redis 4.2, we need 4.3
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
    && echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list \
    && apt-get update \
    && apt-get -y --no-install-recommends install \
        ca-certificates \
        curl \
        unzip \
        sudo \
        git \
        php-apcu \
        php-apcu-bc \
        php7.4-cli \
        php7.4-curl \
        php7.4-soap \
        php7.4-json \
        php7.4-mbstring \
        php7.4-opcache \
        php7.4-readline \
        php7.4-xml \
        php7.4-zip \
        php7.4-fpm \
        php7.4-gmp \
        php7.4-xdebug \
        composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

STOPSIGNAL SIGQUIT

RUN useradd -u 1000 -ms /bin/bash user
RUN echo 'user ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers
USER user

CMD ["/usr/sbin/php-fpm7.4", "-O" ]
EXPOSE 9000
WORKDIR "/application"