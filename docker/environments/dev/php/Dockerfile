FROM node:21.6-alpine AS node
FROM php:8.3.2-fpm-alpine

# Persistent / runtime dependencies
RUN apk add --no-cache \
    acl \
    file \
    gettext \
    unzip \
    oniguruma-dev \
    zlib-dev \
    libzip-dev \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libgd \
    libzip \
    libpng \
    libjpeg \
    libpq \
    libxml2 \
    libxml2-dev \
    supervisor \
    gnu-libiconv \
    autoconf \
    gcc \
    g++ \
    make \
    libc-dev \
    linux-headers \
    git

# Install GD library
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j "$(nproc)" gd

# Install APCU
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# Install PHP extensions
RUN docker-php-ext-install \
    exif \
    opcache \
    xml \
    soap \
    mbstring \
    pdo \
    pdo_mysql \
    zip \
    sysvsem \
    bcmath

# Install FTP
RUN docker-php-ext-install ftp

# Install Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Xdebug from GitHub
RUN git clone https://github.com/xdebug/xdebug.git /usr/src/php/ext/xdebug \
    && cd /usr/src/php/ext/xdebug \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable xdebug

# Install intl extension
RUN apk add --no-cache icu-dev \
    && docker-php-ext-install intl

# Fix iconv library for Alpine
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy configuration files
COPY ./conf/xdebug.ini $PHP_INI_DIR/conf.d/xdebug.ini
COPY ./conf/php.ini $PHP_INI_DIR/php.ini
COPY ./conf/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# Add PHP-FPM configuration
RUN echo 'pm = dynamic' >> $PHP_INI_DIR/../php-fpm.d/www.conf && \
    echo 'pm.max_children = 100' >> $PHP_INI_DIR/../php-fpm.d/www.conf && \
    echo 'pm.start_servers = 32' >> $PHP_INI_DIR/../php-fpm.d/www.conf && \
    echo 'pm.min_spare_servers = 16' >> $PHP_INI_DIR/../php-fpm.d/www.conf && \
    echo 'pm.max_spare_servers = 32' >> $PHP_INI_DIR/../php-fpm.d/www.conf && \
    echo 'pm.max_requests = 200' >> $PHP_INI_DIR/../php-fpm.d/www.conf

# Install JPEGOptim and OptiPNG
RUN apk add jpegoptim optipng

# Supervisor configuration
COPY ./conf/supervisord.conf /etc/supervisord.conf
COPY ./conf/supervisor.ini /etc/supervisor.d/messenger-worker.ini
RUN chmod 644 /etc/supervisord.conf

# Add Node.js from node image
COPY --from=node /usr/lib /usr/lib
COPY --from=node /usr/local/lib /usr/local/lib
COPY --from=node /usr/local/include /usr/local/include
COPY --from=node /usr/local/bin /usr/local/bin
RUN mkdir /.npm && \
    chown -R 501:0 /.npm

# Entrypoint script
ADD ./docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh
ENTRYPOINT ["/docker-entrypoint.sh"]

# Default command
CMD ["php-fpm"]

# Set working directory
WORKDIR /var/www/project