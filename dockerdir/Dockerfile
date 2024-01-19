FROM php:8.3.0-cli-bullseye

WORKDIR /var/shelley

COPY dockerdir/tools/getcomposer.sh /tmp
COPY composer.json /var/shelley/

# Install any additional libraries or tools you need here
RUN rm -rf /var/lib/apt/lists/* \
    && apt update \
    && apt-get install -y git vim \
    && apt update\
    && apt -y install libzip-dev \
    && apt -y install libcurl4-openssl-dev \
    && apt -y install libonig-dev\
    && apt -y install libxml2-dev\
	&& docker-php-ext-configure zip \
	&& docker-php-ext-install -j$(nproc) zip \
	&& docker-php-ext-configure curl \
	&& docker-php-ext-install -j$(nproc) curl \
	&& docker-php-ext-configure mbstring \
	&& docker-php-ext-install -j$(nproc) mbstring \
	&& docker-php-ext-configure dom \
	&& docker-php-ext-install -j$(nproc) dom \
    && /tmp/getcomposer.sh && mv composer.phar /usr/bin/composer && chmod 755 /usr/bin/composer \
    && composer update


