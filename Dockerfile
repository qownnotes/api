FROM composer AS composer

# copying the source directory and install the dependencies with composer
COPY . /app

# run composer install to install the dependencies
RUN composer install \
  --optimize-autoloader \
  --no-interaction

# continue stage build with the desired image and copy the source including the
# dependencies downloaded by composer
FROM trafex/alpine-nginx-php7
USER root
RUN apk --no-cache add php7-tokenizer
USER nobody
COPY --chown=nginx --from=composer /app /var/www
COPY --chown=nginx --from=composer /app/public /var/www/html
