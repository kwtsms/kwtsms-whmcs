# FILE:      tests/e2e/Dockerfile
# CREATED:   2026-11-08 08:33 +03
# TASK:      whmcs-e2e (Boss 2026-11-08 08:26: test all client-side events, save the tests for easy redo)
# AUTHOR:    M Alhassoun
# WHAT:      PHP 8.3 + Apache + ionCube loader, the runtime WHMCS 9.x needs. WHMCS itself is mounted, never baked in.
# WHY:       WHMCS is ionCube-encoded; the plain php image cannot run it.
# USAGE:     built by tests/e2e/compose.yml through run.sh
# DEPENDS:   downloads.ioncube.com (loader tarball)
FROM php:8.3-apache-bookworm
RUN apt-get update -qq && apt-get install -y -qq libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev libxml2-dev unzip curl >/dev/null \
 && docker-php-ext-configure gd --with-freetype --with-jpeg >/dev/null \
 && docker-php-ext-install -j4 pdo_mysql mysqli gd zip intl bcmath soap >/dev/null \
 && a2enmod rewrite >/dev/null \
 && curl -sL -o /tmp/ic.tgz https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz \
 && tar -xzf /tmp/ic.tgz -C /tmp && ext=$(php -r 'echo ini_get("extension_dir");') \
 && cp /tmp/ioncube/ioncube_loader_lin_8.3.so "$ext/" \
 && echo "zend_extension=ioncube_loader_lin_8.3.so" > /usr/local/etc/php/conf.d/00-ioncube.ini \
 && printf 'memory_limit=256M\nmax_execution_time=300\nopcache.enable=0\n' > /usr/local/etc/php/conf.d/whmcs.ini \
 && rm -rf /tmp/ic.tgz /tmp/ioncube /var/lib/apt/lists/*
