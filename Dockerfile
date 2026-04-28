# Accepted values: 8.4 - 8.3 - 8.2
ARG PHP_VERSION=8.4

ARG COMPOSER_VERSION=2.8.9

###########################################
# Build frontend assets with Bun
###########################################

ARG BUN_VERSION=1

FROM oven/bun:${BUN_VERSION} AS build

ENV ROOT=/var/www/html

WORKDIR ${ROOT}

COPY --link package.json bun.lock ./

RUN bun install

COPY --link . .

RUN bun run build

###########################################
# Builder stage: compile PHP extensions, install deps
###########################################

FROM composer:${COMPOSER_VERSION} AS vendor

FROM php:${PHP_VERSION}-cli-alpine AS builder

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC

ENV TERM=xterm-color \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    OCTANE_SERVER=swoole \
    USER=octane \
    ROOT=/var/www/html \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24

WORKDIR ${ROOT}

SHELL ["/bin/sh", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
  && echo ${TZ} > /etc/timezone

ARG IPE_VERSION=2.5.3
ARG IPE_SHA256=4d1bd0678b0c63531beebdc488126401f2ff5db0476819649bc29b10750a94f7

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/${IPE_VERSION}/install-php-extensions /usr/local/bin/

RUN echo "${IPE_SHA256}  /usr/local/bin/install-php-extensions" | sha256sum -c

RUN apk update; \
    apk upgrade; \
    apk add --no-cache \
    curl \
    wget \
    git \
    ca-certificates \
    supervisor \
    libsodium-dev \
    && install-php-extensions \
    bz2 \
    pcntl \
    mbstring \
    bcmath \
    sockets \
    pgsql \
    pdo_pgsql \
    opcache \
    exif \
    zip \
    intl \
    gd \
    redis \
    rdkafka \
    memcached \
    igbinary \
    ldap \
    swoole \
    curl \
    dom \
    xml \
    simplexml \
    fileinfo \
    tokenizer \
    posix \
    pdo \
    && docker-php-source delete \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

ARG SUPERCRONIC_VERSION=0.2.33

RUN arch="$(apk --print-arch)" \
    && case "$arch" in \
    armhf) _cronic_fname='supercronic-linux-arm' && _cronic_sha256='d76a1409cd1365c4bb42e1565773e820599c7fdfbd55e18c055b61d2ef66716e' ;; \
    aarch64) _cronic_fname='supercronic-linux-arm64' && _cronic_sha256='f1f8585c66de020fef494dd636058f99949d108f569fef00016a1c8b9eb145b3' ;; \
    x86_64) _cronic_fname='supercronic-linux-amd64' && _cronic_sha256='feefa310da569c81b99e1027b86b27b51e6ee9ab647747b49099645120cfc671' ;; \
    x86) _cronic_fname='supercronic-linux-386' && _cronic_sha256='245063d7cda695319139fccc02ff1d25a0fa6f3773330db103f3b16d170c31f2' ;; \
    *) echo >&2 "error: unsupported architecture: $arch"; exit 1 ;; \
    esac \
    && wget -q "https://github.com/aptible/supercronic/releases/download/v${SUPERCRONIC_VERSION}/${_cronic_fname}" \
    -O /usr/bin/supercronic \
    && echo "${_cronic_sha256}  /usr/bin/supercronic" | sha256sum -c \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel

RUN addgroup -g ${WWWGROUP} ${USER} \
    && adduser -D -h ${ROOT} -G ${USER} -u ${WWWUSER} -s /bin/sh ${USER}

RUN mkdir -p /var/log/supervisor /var/run/supervisor \
    && chown -R ${USER}:${USER} ${ROOT} /var/log/supervisor /var/run/supervisor \
    && chmod -R a+rw ${ROOT}/storage ${ROOT}/bootstrap/cache \
    && chmod 750 /var/log/supervisor /var/run/supervisor

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

USER ${USER}

COPY --link --chown=${USER}:${USER} --from=vendor /usr/bin/composer /usr/bin/composer
COPY --link --chown=${USER}:${USER} composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts

RUN composer clear-cache

COPY --link --chown=${USER}:${USER} . .
COPY --link --chown=${USER}:${USER} --from=build ${ROOT}/public public

RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache && chmod -R a+rw storage

COPY --link --chown=${USER}:${USER} deployment/supervisord.conf /etc/supervisor/
COPY --link --chown=${USER}:${USER} deployment/octane/Swoole/supervisord.swoole.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/octane/FrankenPHP/supervisord.frankenphp.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/octane/RoadRunner/supervisord.roadrunner.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/supervisord.*.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini
COPY --link --chown=${USER}:${USER} deployment/start-container /usr/local/bin/start-container
COPY --link --chown=${USER}:${USER} deployment/healthcheck /usr/local/bin/healthcheck

RUN composer install \
    --classmap-authoritative \
    --no-interaction \
    --no-ansi \
    --no-dev \
    && composer clear-cache

RUN chmod +x /usr/local/bin/start-container /usr/local/bin/healthcheck

###########################################
# Production stage: minimal runtime
###########################################

FROM php:${PHP_VERSION}-cli-alpine AS production

LABEL maintainer="SMortexa <seyed.me720@gmail.com>"
LABEL org.opencontainers.image.title="Laravel Octane Dockerfile"
LABEL org.opencontainers.image.description="Production-ready Dockerfile for Laravel Octane"
LABEL org.opencontainers.image.source=https://github.com/exaco/laravel-octane-dockerfile
LABEL org.opencontainers.image.licenses=MIT

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC

ENV TERM=xterm-color \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    OCTANE_SERVER=swoole \
    USER=octane \
    ROOT=/var/www/html \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24

WORKDIR ${ROOT}

SHELL ["/bin/sh", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
  && echo ${TZ} > /etc/timezone

RUN apk update; \
    apk upgrade; \
    apk add --no-cache \
    curl \
    supervisor \
    tini \
    ca-certificates \
    libsodium \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    libzip \
    oniguruma \
    icu-libs \
    libavif \
    openldap \
    libpq \
    librdkafka \
    lz4-libs \
    libssh2 \
    libmemcached-libs \
    bzip2-libs \
    brotli-libs \
    zstd-libs \
    c-ares \
    libxml2 \
    libxslt \
    libcurl \
    busybox-extras \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/etc/php/ /usr/local/etc/php/
COPY --from=builder /usr/local/lib/php/ /usr/local/lib/php/
COPY --from=builder /usr/bin/supercronic /usr/bin/supercronic
COPY --from=builder /etc/supercronic/laravel /etc/supercronic/laravel

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

RUN addgroup -g ${WWWGROUP} ${USER} \
    && adduser -D -h ${ROOT} -G ${USER} -u ${WWWUSER} -s /bin/sh ${USER}

RUN mkdir -p /var/log/supervisor /var/run/supervisor \
    && chown -R ${USER}:${USER} ${ROOT} /var/log/supervisor /var/run/supervisor \
    && chmod -R a+rw ${ROOT}/storage ${ROOT}/bootstrap/cache \
    && chmod 750 /var/log/supervisor /var/run/supervisor

COPY --from=builder --chown=${USER}:${USER} ${ROOT} ${ROOT}

COPY --link --chown=${USER}:${USER} deployment/supervisord.conf /etc/supervisor/
COPY --link --chown=${USER}:${USER} deployment/octane/Swoole/supervisord.swoole.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/octane/FrankenPHP/supervisord.frankenphp.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/octane/RoadRunner/supervisord.roadrunner.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/supervisord.*.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini
COPY --link --chown=${USER}:${USER} deployment/php-ini-security.ini ${PHP_INI_DIR}/conf.d/98-security.ini
COPY --link --chown=${USER}:${USER} deployment/start-container /usr/local/bin/start-container
COPY --link --chown=${USER}:${USER} deployment/healthcheck /usr/local/bin/healthcheck

RUN chmod +x /usr/local/bin/start-container /usr/local/bin/healthcheck

USER ${USER}

EXPOSE 8000

ENTRYPOINT ["/sbin/tini", "--", "start-container"]

HEALTHCHECK --start-period=30s --interval=5s --timeout=3s --retries=8 CMD healthcheck || exit 1