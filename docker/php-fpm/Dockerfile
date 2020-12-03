FROM debian:buster

ENV DEBIAN_FRONTEND=noninteractive

# Basics
RUN apt-get update && apt-get install -y wget lsb-release

# Install PHP and the rest
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
        php8.0-cli \
        php8.0-curl \
        php8.0-soap \
        php8.0-iconv \
        php8.0-mbstring \
        php8.0-opcache \
        php8.0-readline \
        php8.0-xml \
        php8.0-zip \
        php8.0-fpm \
        php8.0-gmp \
        php8.0-xdebug \
        php8.0-sqlite3 \
        composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

STOPSIGNAL SIGQUIT

RUN useradd -u 1000 -ms /bin/bash user
RUN echo 'user ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers
USER user

CMD ["/usr/sbin/php-fpm8.0", "-O" ]
EXPOSE 9000
WORKDIR "/application"