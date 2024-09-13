FROM debian:bookworm

ENV DEBIAN_FRONTEND=noninteractive

# Basics
RUN apt-get update && apt-get install -y wget lsb-release

# Install PHP and the rest
RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        ca-certificates \
        curl \
        unzip \
        sudo \
        git \
        php-apcu \
        php-cli \
        php-curl \
        php-soap \
        php-iconv \
        php-mbstring \
        php-opcache \
        php-readline \
        php-xml \
        php-zip \
        php-fpm \
        php-gmp \
        php-xdebug \
        php-sqlite3 \
        composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

STOPSIGNAL SIGQUIT

RUN useradd -u 1000 -ms /bin/bash user
RUN echo 'user ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers
USER user

CMD ["/usr/sbin/php-fpm8.2", "-O" ]
EXPOSE 9000
WORKDIR "/application"

# disable xdebug by default, it makes everything slow
RUN sudo phpdismod xdebug
