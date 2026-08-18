# PHP 8.2 with Apache + mod_php. Apache serves www/ and runs PHP in-process
# (no php-fpm), so opcache (configured below) is shared across requests.
FROM php:8.2-apache

# MySQL access: mysqli (procedural/OO MySQL API) plus PDO and its MySQL
# driver (pdo_mysql) for the PDO-based data layer.
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Xdebug — step debugging during development. Settings live in xdebug.ini
# (copied below): mode=debug + start_with_request=trigger, so it stays
# dormant on normal requests and only attaches when the IDE sends a trigger.
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# APCu — in-memory user cache. Spwa\Cache\RenderCache uses it to stash the
# rendered OLD tree so POST event handling can skip re-rendering it.
#   apc.enabled / enable_cli : on for web and CLI requests
#   apc.shm_size=64M         : shared-memory pool size for the cache
RUN pecl install apcu \
    && docker-php-ext-enable apcu \
    && { \
        echo 'apc.enabled=1'; \
        echo 'apc.enable_cli=1'; \
        echo 'apc.shm_size=64M'; \
    } > /usr/local/etc/php/conf.d/apcu.ini

# OPcache — caches compiled bytecode so each request stops recompiling every
# class file (the dominant initial-render cost; see the timing instrumentation
# in spwa.js / Spwa::execMs). Per-setting:
#   enable / enable_cli        : on for web and CLI
#   memory_consumption=128     : MB of shared memory for cached bytecode
#   interned_strings_buffer=16 : MB for interned (deduplicated) strings
#   max_accelerated_files=10000: cache slots — keep above the total .php count
#   validate_timestamps=1
#   revalidate_freq=0          : re-stat each file's mtime every request, so
#                                code edits (and HMR reloads) are picked up
#                                immediately — the right dev tradeoff.
# For production, set validate_timestamps=0 and reload on deploy instead.
RUN docker-php-ext-enable opcache \
    && { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Apache mod_rewrite — required for SPWA's front-controller routing
# (all paths rewritten to index.php).
RUN a2enmod rewrite && service apache2 restart

# Writable /tmp — holds xdebug.log (see xdebug.ini) and other scratch output.
RUN mkdir -p /tmp && chmod 777 /tmp

# Composer — PHP dependency manager, used to install/update vendor packages.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Reinstall dependencies on every container start. `composer install` is
# typically run on the host (macOS), where path-repository packages are
# symlinked relative to the host's directory nesting — e.g. ../brickphp
# resolves to the sibling ~/projects/brickphp, so vendor/brickphp/brickphp
# becomes a 4-levels-up symlink. That depth is wrong inside the container,
# where www is mounted at /var/www/html and the framework at /var/www/brickphp
# (only 3 levels up), so the host-generated link dangles and the autoloader
# can't find BrickPHP\* classes.
#
# Running `composer install` here (not just `dump-autoload`, which leaves the
# symlinks untouched) recreates every path-package symlink relative to the
# container's filesystem view before Apache starts serving. The result is a
# 3-levels-up link that is valid in BOTH the container and on the host. All
# dependencies are path repos (packagist is disabled), so this is fully
# offline and idempotent.
CMD composer install --no-interaction --working-dir=/var/www/html && apache2-foreground
