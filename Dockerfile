FROM composer:2.0.9 AS composer

# copying the source directory and install the dependencies with composer
COPY . /app

# run composer install to install the dependencies
RUN composer install \
  --optimize-autoloader \
  --no-interaction

RUN composer dump-env prod

# continue stage build with the desired image and copy the source including the
# dependencies downloaded by composer
# https://github.com/TrafeX/docker-php-nginx/
FROM trafex/alpine-nginx-php7

USER root
RUN apk --no-cache add php7-tokenizer php7-pdo php7-pdo_sqlite php7-iconv bash && apk --no-cache del php7-mysqlnd php7-mysqli

# add supervisor config for our own tasks
COPY docker.prod/supervisord-qon.conf /tmp/supervisord-qon.conf
RUN cat /tmp/supervisord-qon.conf >> /etc/supervisor/conf.d/supervisord.conf
RUN rm /tmp/supervisord-qon.conf

USER nobody
COPY --chown=nginx --from=composer /app /var/www
COPY --chown=nginx --from=composer /app/public /var/www/html
