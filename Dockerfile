FROM php:8.2-apache

# pdo_sqlite ships enabled in the official image; only the vhost needs work.
# remoteip recovers the real client IP from the reverse proxy, and the
# ServerTokens/ServerSignature pair is appended after conf-enabled/ so it wins
# over Debian's security.conf.
RUN a2enmod rewrite remoteip \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf \
    && printf '\nServerTokens Prod\nServerSignature Off\n' >> /etc/apache2/apache2.conf \
    && docker-php-ext-install opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
COPY src ./src
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/remoteip.conf /etc/apache2/conf-enabled/remoteip.conf

COPY . .

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
