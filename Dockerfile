# Accepted values: 8.4 - 8.3 - 8.2
ARG PHP_VERSION=8.4

ARG COMPOSER_VERSION=2.8.9

ARG ALPINE_VERSION=3.21

###########################################
# Build frontend assets with Bun
###########################################

ARG BUN_VERSION=1.2.9

FROM oven/bun:${BUN_VERSION} AS build

ENV ROOT=/var/www/html

WORKDIR ${ROOT}

COPY --link package.json bun.lock ./

RUN HUSKY=0 bun install

COPY --link . .

RUN bun run build

###########################################
# Composer binary stage
###########################################

FROM composer:${COMPOSER_VERSION} AS vendor

###########################################
# Build supercronic with a patched Go toolchain
# Upstream v0.2.47 ships Go 1.26.4; rebuild with 1.26.5 for CVE-2026-39822.
###########################################

ARG SUPERCRONIC_VERSION=0.2.47
ARG GOLANG_VERSION=1.26.5

FROM golang:${GOLANG_VERSION}-alpine AS supercronic

ARG SUPERCRONIC_VERSION

ENV CGO_ENABLED=0 \
    GOTOOLCHAIN=local

RUN go install github.com/aptible/supercronic@v${SUPERCRONIC_VERSION}

###########################################
# Builder stage: compile PHP extensions, install deps
###########################################

FROM php:${PHP_VERSION}-cli-alpine${ALPINE_VERSION} AS builder

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

ARG IPE_VERSION=2.10.20
ARG IPE_SHA256=d1eaf1a8a57fd36647ab46d55a781d49d3929aeaad038a1734793d3d21467de7

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/${IPE_VERSION}/install-php-extensions /usr/local/bin/

RUN echo "${IPE_SHA256}  /usr/local/bin/install-php-extensions" | sha256sum -c

RUN apk update; \
    apk upgrade; \
    apk add --no-cache \
    curl \
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

RUN mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel

RUN addgroup -g ${WWWGROUP} ${USER} \
    && adduser -D -h ${ROOT} -G ${USER} -u ${WWWUSER} -s /bin/sh ${USER} \
    && cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini \
    && mkdir -p /var/log/supervisor /var/run/supervisor ${ROOT}/storage ${ROOT}/bootstrap/cache \
    && chown -R ${USER}:${USER} ${ROOT} /var/log/supervisor /var/run/supervisor \
    && chmod -R a+rw ${ROOT}/storage ${ROOT}/bootstrap/cache \
    && chmod 750 /var/log/supervisor /var/run/supervisor

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

FROM php:${PHP_VERSION}-cli-alpine${ALPINE_VERSION} AS production

LABEL maintainer="SMortexa <seyed.me720@gmail.com>"
LABEL org.opencontainers.image.title="Laravel Octane Dockerfile"
LABEL org.opencontainers.image.description="Production-ready Dockerfile for Laravel Octane"
LABEL org.opencontainers.image.source=https://github.com/exaco/laravel-octane-dockerfile
LABEL org.opencontainers.image.licenses=MIT

ARG VCS_REF
ARG VERSION
LABEL org.opencontainers.image.revision=${VCS_REF}
LABEL org.opencontainers.image.version=${VERSION}

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
    bzip2 \
    brotli-libs \
    zstd-libs \
    c-ares \
    libxml2 \
    libxslt \
    libcurl \
    libxpm \
    libx11 \
    liburing \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/lib/libfbclient.so* /usr/lib/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=supercronic /go/bin/supercronic /usr/bin/supercronic
COPY --from=builder /etc/supercronic/laravel /etc/supercronic/laravel

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini \
    && addgroup -g ${WWWGROUP} ${USER} \
    && adduser -D -h ${ROOT} -G ${USER} -u ${WWWUSER} -s /bin/sh ${USER} \
    && mkdir -p /var/log/supervisor /var/run/supervisor ${ROOT}/storage ${ROOT}/bootstrap/cache \
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