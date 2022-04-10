FROM php:7.4-apache
MAINTAINER monolite.net

ENV PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

#Apt-get library cache update.
RUN apt-get update


#Ntp install. Ntp necessary to keep the server time updated automatically
#
# ATTENTION, replace the indicated time zone with the correct one
#
RUN apt-get install -y ntp
ARG TIMEZONE=Europe/Rome
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone

RUN service ntp restart


#Apache module, php extensions and compsoer installation
RUN apt-get install -y zip unzip libzip-dev
RUN docker-php-ext-install zip

RUN a2enmod rewrite



RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"


#PHP.ini timezone configuration
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
RUN sed -ie 's/\;date\.timezone\ =/date\.timezone\ =\ '$TIMEZONE'/g' /usr/local/etc/php/php.ini


RUN cd /var/www/html/
ARG VERSION=""
RUN composer create-project lucatacconi/crunz-ui$VERSION

#Crunz configuration installation
#
# ATTENTION, replace the time zone and configurations indicated with the correct ones
#
COPY crunz.yml /var/www/html/crunz-ui/
RUN chmod +x /var/www/html/crunz-ui/crunz-ui.sh


#Crunz-ui directory owner configuration
RUN chown -R www-data:www-data /var/www/html/crunz-ui



#Crontab installation and configuration for running crunz-ui task manager
RUN apt-get install -y cron

RUN echo '* * * * * export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin" && cd /var/www/html/crunz-ui && ./crunz-ui.sh\n' >> /etc/cron.d/cronlst
RUN echo '# Empty line\n' >> /etc/cron.d/cronlst

RUN chmod 0644 /etc/cron.d/cronlst

# # Apply cron job
RUN crontab /etc/cron.d/cronlst


#Apache Crunz-ui directory configuration
RUN echo 'Alias "/crunz-ui" "/var/www/html/crunz-ui"\n' >> /etc/apache2/conf-available/crunz-ui.conf
RUN echo '<Directory /var/www/html/crunz-ui>\n' >> /etc/apache2/conf-available/crunz-ui.conf
RUN echo '      Options Indexes\n' >> /etc/apache2/conf-available/crunz-ui.conf
RUN echo '      AllowOverride All\n' >> /etc/apache2/conf-available/crunz-ui.conf
RUN echo '      Require all granted\n' >> /etc/apache2/conf-available/crunz-ui.conf
RUN echo '</Directory>' >> /etc/apache2/conf-available/crunz-ui.conf

RUN ln -s /etc/apache2/conf-available/crunz-ui.conf /etc/apache2/conf-enabled


#Apache and cron start
CMD service apache2 restart && cron -f


#Exposure of the port selected for the web service
#
# ATTENTION, replace the port configuration with the correct one if different from 80
#
EXPOSE 80
