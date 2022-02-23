FROM php:7.4-apache
MAINTAINER monolite.net

#Apt-get library cache update.
RUN apt-get update


#Ntp install. Ntp necessary to keep the server time updated automatically
#
# ATTENTION, replace the indicated time zone with the correct one
#
RUN apt-get install -y ntp
ENV TZ=Europe/Rome
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN service ntp restart


#Apache module, php extensions and compsoer installation
RUN apt-get install -y zip unzip libzip-dev
RUN docker-php-ext-install zip

RUN a2enmod rewrite

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"


#Apache Crunz-ui directory configuration
COPY crunz-ui.conf /etc/apache2/conf-available/
RUN ln -s /etc/apache2/conf-available/crunz-ui.conf /etc/apache2/conf-enabled


#Apache restart to acquire the loaded configurations
RUN service apache2 restart


RUN cd /var/www/html/
RUN composer create-project lucatacconi/crunz-ui

#Crunz configuration installation
#
# ATTENTION, replace the time zone and configurations indicated with the correct ones
#
COPY crunz.yml /var/www/html/crunz-ui/

#Crunz-ui directory owner configuration
RUN chown -R www-data:www-data /var/www/html/crunz-ui


#Crontab installation and configuration for running crunz-ui task manager
RUN apt-get install -y cron
RUN crontab -l | { cat; echo "* * * * * bash cd /var/www/html/crunz-ui && ./crunz-ui.sh"; } | crontab -

CMD cron

#Exposure of the port selected for the web service
#
# ATTENTION, replace the port configuration with the correct one if different from 80
#
EXPOSE 80
