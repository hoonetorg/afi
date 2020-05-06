#tested mybi
# php:7-apache

ARG mybi=php:7-apache

FROM ${mybi}

ARG myuser=www-data
ARG mygroup=www-data

COPY afi /var/www/html/afi
COPY php.ini /usr/local/etc/php/php.ini

RUN chown -R $myuser:$mygroup /var/www/html/afi \
    && a2enmod rewrite
