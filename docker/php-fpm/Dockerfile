FROM debian:trixie

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

# disable xdebug by default, it makes everything slow
RUN phpdismod xdebug

# Ensure PHP-FPM can write to necessary directories
RUN mkdir -p /home/user && chown -R user:user /home/user

USER user

CMD ["/usr/sbin/php-fpm8.4", "-F", "-y", "/etc/php/8.4/fpm/php-fpm.conf"]
EXPOSE 9000
WORKDIR "/application"

