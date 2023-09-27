# Crunz-ui install example on a Nginx/PHP Ubuntu server

Here is the procedure for activating Crunz-ui with the use of Crunz embedded in a newly installed Ubuntu/Nginx/PHP server.

Update of system packages and installation of net-tools to have tools like ifconfig and others available:

```
sudo apt-get update
sudo apt-get upgrade
sudo apt install net-tools
```

Installation of the ntp service which keeps the time always synchronized and verification of the system time:
```
sudo apt-get install ntp
sudo timedatectl set-timezone Europe/Rome
sudo service ntp restart
timedatectl
```
I.e.: sudo timedatectl set-timezone Europe/Rome

Installation of Nginx server, PHP, and Curl for PHP:
```
sudo apt-get install nginx
sudo apt-get install php-fpm
sudo apt-get install php-curl
sudo apt-get install zip unzip php-zip
```

or, if you prefer, all in a single line:
```
sudo apt-get install nginx php-fpm php-curl zip unzip php-zip
```

Check your nginx installation and the installed version:
```
sudo systemctl status php8.1-fpm
```

Installation of composer
```
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

Download and installation Crunz-ui in user home:
```
cd
composer create-project lucatacconi/crunz-ui
```

Configuration of Crunz time zone:
```
cd crunz-ui
./vendor/bin/crunz publish:config
```

Move Crunz-ui directory to the corret destination and permissions configuration:
```
cd
sudo mv ./crunz-ui /var/www/html/
sudo chown -R www-data:www-data /var/www/html/crunz-ui
```


Configuration of crontab (crontab -e) adding crunz-ui service execution scheduling:

```
* * * * * cd /var/www/html/crunz-ui && ./crunz-ui.sh
```

> :warning: **The user who owns the entry in the crontab must have permissions to write to the crunz-ui folder (ex www-data, root, ..)**

> :warning: **Crunz and Crunz-ui need the ability to set crontab to schedule the regular execution of task management process. Crontab entry owner must be carefully selected: using high permissions level user (ex root) could allow tasks to access sensitive files or perform malicious operations on the entire server file system.**
