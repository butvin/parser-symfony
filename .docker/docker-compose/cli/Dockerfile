ARG PHP_VERSION=7.4
FROM php:${PHP_VERSION}-cli

ARG DEBIAN_FRONTEND=noninteractive

RUN set -ex;
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y \
        apt-utils \
        locales \
        librabbitmq-dev \
        libimage-exiftool-perl \
        libzip-dev \
        zip \
        unzip \
        p7zip-full \
        sendmail

# Amqp (rabbitMQ), xDebug
RUN pecl install amqp
RUN docker-php-ext-enable amqp
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug \
    && touch /var/log/xdebug.log
# Additional extentions
RUN docker-php-ext-install zip mysqli pdo pdo_mysql opcache
# Redis
RUN pecl install -o -f redis
RUN docker-php-ext-enable redis

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --filename=composer \
    --install-dir=/usr/local/bin && \
    echo "alias composer='COMPOSER_MEMORY_LIMIT=-1 composer'" >> /root/.bashrc \
    && composer  --version

# Set the locale
RUN sed -i '/en_US.UTF-8/s/^# //g' /etc/locale.gen
RUN locale-gen

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

RUN echo "sendmail_path=/usr/sbin/sendmail -t -i" >> /usr/local/etc/php/conf.d/sendmail.ini
RUN sed -i '/#!\/bin\/sh/aservice sendmail restart' /usr/local/bin/docker-php-entrypoint
RUN sed -i '/#!\/bin\/sh/aecho "$(hostname -i)\t$(hostname) $(hostname).localhost" >> /etc/hosts' /usr/local/bin/docker-php-entrypoint

COPY php.ini "$PHP_INI_DIR"/conf.d
COPY xdebug.ini "$PHP_INI_DIR"/conf.d
RUN rm -rf /tmp/pear

WORKDIR "/application"

CMD cron -f