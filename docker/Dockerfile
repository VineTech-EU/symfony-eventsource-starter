#######################################################
####################### BASE ##########################
#######################################################
FROM php:8.4-fpm-bookworm AS base

ENV APPIZE_DEPS \
    wget \
    git \
    libxml2-dev \
    libgmp-dev \
    libicu-dev \
    libpq-dev \
    librabbitmq-dev \
    libzip-dev

RUN apt-get update && apt-get install  -y \
    unzip \
    libpq5 \
    libzip4 \
    librabbitmq4 \
    $APPIZE_DEPS \
    $PHPIZE_DEPS && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    mkdir -p /usr/src/php/ext/apcu && \
    curl -fsSL https://pecl.php.net/get/apcu | tar xvz -C "/usr/src/php/ext/apcu" --strip 1 && \
    mkdir -p /usr/src/php/ext/amqp && \
    curl -fsSL https://pecl.php.net/get/amqp/1.11.0 | tar xvz -C "/usr/src/php/ext/amqp" --strip 1 && \
    mkdir -p /usr/src/php/ext/redis && \
    curl -fsSL https://pecl.php.net/get/redis | tar xvz -C "/usr/src/php/ext/redis" --strip 1 && \
    docker-php-ext-configure pcntl --enable-pcntl && \
    docker-php-ext-install -j$(nproc) zip intl pdo_pgsql bcmath apcu gmp soap amqp pcntl redis && \
    docker-php-ext-enable opcache && \
    apt-get purge -y --auto-remove $APPIZE_DEPS $PHPIZE_DEPS && \
    docker-php-source delete

WORKDIR /srv

COPY docker/php/pool.conf.ini $PHP_INI_DIR/../php-fpm.d/www.conf
RUN chmod 644 $PHP_INI_DIR/../php-fpm.d/www.conf

EXPOSE 9000

############################
######### BASE-DEV #########
############################

FROM base AS base-dev

RUN set -eux && \
    apt-get update && apt-get install -y gnupg2 $APPIZE_DEPS $PHPIZE_DEPS && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    mkdir -p /usr/src/php/ext/pcov && curl -fsSL https://pecl.php.net/get/pcov | tar xvz -C "/usr/src/php/ext/pcov" --strip 1 && \
    docker-php-ext-install -j$(nproc) pcov && \
    rm $PHP_INI_DIR/conf.d/docker-php-ext-pcov.ini && \
    docker-php-source delete && \
    apt-get purge -y --auto-remove $APPIZE_DEPS $PHPIZE_DEPS

COPY docker/php/php.dev.ini $PHP_INI_DIR/conf.d/php.ini
ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /usr/local/bin/wait-for-it

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" && \
    chmod 644 $PHP_INI_DIR/conf.d/php.ini && \
    chmod +x /usr/local/bin/wait-for-it

#######################
######### DEV #########
#######################
FROM base-dev AS dev

ARG HOST_USERID=1000
ARG HOST_GROUPID=1000

RUN apt-get update && apt-get install -y autoconf g++ gcc make && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

ADD https://pecl.php.net/get/xdebug /tmp/xdebug.tar.gz
RUN mkdir -p /usr/src/php/ext/xdebug && \
    tar xzf /tmp/xdebug.tar.gz -C "/usr/src/php/ext/xdebug" --strip 1 && \
    rm /tmp/xdebug.tar.gz && \
    docker-php-ext-install -j$(nproc) xdebug && \
    rm $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer/composer:latest-bin /composer /usr/local/bin/composer

# user sed instead of usermod to avoid fs leaks on mac os
RUN sed -i -E "s/www-data(:.*:).*:.*(:.*:).*(:.*)/www-data\1${HOST_USERID}:${HOST_GROUPID}\2\/home\/www\3/" /etc/passwd

RUN apt-get update && apt-get install zsh fzf nano curl git -y && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN mkdir "/home/www" && chown -R www-data:www-data "/home/www"

USER www-data
RUN curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh -o /tmp/install-omz.sh && \
    sh /tmp/install-omz.sh --unattended && \
    rm /tmp/install-omz.sh
RUN git clone https://github.com/zsh-users/zsh-syntax-highlighting.git ${ZSH_CUSTOM:-~/.oh-my-zsh/custom}/plugins/zsh-syntax-highlighting
RUN git clone https://github.com/joshskidmore/zsh-fzf-history-search ${ZSH_CUSTOM:=~/.oh-my-zsh/custom}/plugins/zsh-fzf-history-search
RUN git clone --depth=1 https://github.com/romkatv/powerlevel10k.git ${ZSH_CUSTOM:-$HOME/.oh-my-zsh/custom}/themes/powerlevel10k
COPY docker/.zshrc /home/www
COPY docker/.p10k.zsh /home/www
USER root

########################
######### PROD #########
########################
FROM base AS prod

ENV APP_ENV "prod"

COPY --from=composer/composer:latest-bin /composer /usr/local/bin/composer

COPY ./ /srv
ENV COMPOSER_HOME /srv/.composer
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-scripts --classmap-authoritative && \
    bin/console assets:install && \
    bin/console cache:clear && \
    bin/console cache:warmup && \
    rm -rf ./var/cache/prod && \
    chown -R www-data:www-data /srv/var

############################
######### PROD-NGINX #######
############################
FROM nginx:alpine AS prod-nginx

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/symfony.conf /etc/nginx/symfony.conf
COPY --from=prod /srv/public /srv/public
