FROM composer:2 AS composer

# copying the source directory and install the dependencies with composer
COPY . /app

# run composer install to install the dependencies
RUN composer install \
  --optimize-autoloader \
  --no-interaction

RUN composer dump-env prod

# continue stage build with the desired image and copy the source including the
# dependencies downloaded by composer
# https://dockerfile.readthedocs.io/en/latest/content/DockerImages/dockerfiles/php-apache.html
FROM webdevops/php-nginx:8.3-alpine

USER root

# add supervisor config for our own tasks
COPY docker.prod/supervisord-qon.conf /opt/docker/etc/supervisor.d/supervisord-qon.conf

USER application
COPY --chown=application --from=composer /app /app

# create cache dir
RUN mkdir -p /app/var/cache/prod

# change web root
ENV WEB_DOCUMENT_ROOT /app/public

# To prevent `failed switching to "root"` errors
# See https://github.com/webdevops/Dockerfile/issues/366
USER root
