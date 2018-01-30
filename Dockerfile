FROM php:7.1-cli-alpine

# Install tzdata and change to Europe/Berlin
RUN  set -x && \
    apk --update add tzdata && \
    ln -sf /usr/share/zoneinfo/Europe/Berlin /etc/localtime && \
    date

RUN apk add --no-cache $PHPIZE_DEPS && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    rm -rf /var/cache/apk/*

ARG user
ARG home

RUN adduser -D -H -u 1000 "$user" -h "$home"

ENV COMPOSER_HOME "$home"
RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer
ENV PATH "${COMPOSER_HOME}/vendor/bin:${PATH}"

CMD ["/usr/bin/php" , "-a"]
