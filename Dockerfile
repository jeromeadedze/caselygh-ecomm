# Runs Casely on Railway: PHP with Apache, so the project's .htaccess rules keep working.
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql

COPY . /var/www/html/
WORKDIR /var/www/html

# Settings come from environment variables, not from the git-ignored local files.
RUN cp deploy/db_cred.php core/db_cred.php \
    && cp deploy/app_config.php core/app_config.php \
    && cp deploy/php.ini "$PHP_INI_DIR/conf.d/casely.ini" \
    && cp deploy/apache.conf /etc/apache2/conf-enabled/casely.conf \
    && cp deploy/start.sh /usr/local/bin/casely-start \
    && chmod +x /usr/local/bin/casely-start \
    && rm -rf deploy Dockerfile .dockerignore \
    && cp -R uploads /usr/src/casely-uploads \
    && chown -R www-data:www-data /var/www/html

CMD ["casely-start"]
