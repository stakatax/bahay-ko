FROM php:8.2-apache-bookworm

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libzip-dev libxml2-dev unzip ca-certificates \
    && docker-php-ext-install mysqli mbstring dom zip opcache \
    && a2enmod rewrite headers setenvif \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts \
    && composer check-platform-reqs --no-dev
COPY index.php ./
COPY app/ ./app/
COPY config/ ./config/
COPY include/ ./include/
COPY pages/ ./pages/
COPY Assets/ ./Assets/
COPY scripts/dispatch_browser_push.php scripts/dispatch_publication_notifications.php scripts/check_deployment_configuration.php ./scripts/
COPY deployment/docker/ /opt/olshco/
COPY deployment/docker/email-wrapper.php ./config/email.local.php
COPY deployment/docker/push-wrapper.php ./config/push.local.php
COPY deployment/docker/apache.conf /etc/apache2/conf-available/olshco.conf
COPY deployment/docker/php.ini /usr/local/etc/php/conf.d/olshco.ini
RUN a2enconf olshco \
    && mkdir -p /opt/olshco/upload-security/documents /opt/olshco/upload-security/profile-photos \
    && cp Assets/uploads/.htaccess /opt/olshco/upload-security/.htaccess \
    && cp Assets/uploads/documents/.htaccess /opt/olshco/upload-security/documents/.htaccess \
    && cp Assets/uploads/profile-photos/.htaccess /opt/olshco/upload-security/profile-photos/.htaccess \
    && chmod +x /opt/olshco/entrypoint.sh /opt/olshco/worker.sh \
    && find /opt/olshco -name '*.sh' -exec sed -i 's/\r$//' {} + \
    && mkdir -p /var/www/html/logs
ENTRYPOINT ["/opt/olshco/entrypoint.sh"]
CMD ["apache2-foreground"]
