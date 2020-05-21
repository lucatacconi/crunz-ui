# APACHE su Ubuntu

Here is the procedure for activating Crunz-ui with the use of Crunz embedded on a newly installed Ubuntu system.

Update of system packages and installation of net-tools to have tools like ifconfig and others available:

```
sudo apt-get update
sudo apt-get upgrade
sudo apt install net-tools
```

Installation of the ntp service which keeps the time always synchronized and verification of the system time:
```
sudo apt-get install ntp
sudo service ntp restart
timedatectl
```

Installation of Apache server, PHP, and Curl for PHP:

```
sudo apt-get install apache2
sudo apt-get install php libapache2-mod-php
sudo apt-get install php-curl
```

Enable apache server as a service to be activated at boot time:

```
systemctl enable apache2
```

Editing to apache configuration file /etc/apache2/apache2.conf to add Crunz-ui location mapping:

```
Alias "/crunz-ui" "/var/www/html/crunz-ui"
<Directory /var/www/html/crunz-ui>
        Options Indexes
        AllowOverride All
        Require all granted
</Directory>
```

Configuration of the rewrite module necessary for the functioning of the Crunz-ui API. This operation require apache server restart.
```
ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load
sudo service apache2 restart
```

Installation of composer
```
sudo apt-get install composer
```

Adding the new user crunz_usr:
```
sudo useradd -m crunz_usr
sudo adduser crunz_usr www-data
```


Installation of Crunz-ui and permissions configuration:
```
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R g+w /var/www/html


sudo su crunz_usr
cd /var/www/html
composer create-project lucatacconi/crunz-ui

sudo chmod -R g+w /var/www/html/crunz-ui
sudo chown -R crunz_usr:www-data /var/www/html/crunz-ui
```

Configuration of Crunz time zone:
```
sudo su crunz_usr
cd /var/www/html/crunz-ui
./vendor/bin/crunz publish:config
```

Configuration of crontab (crontab -e) adding crunz-ui service execution scheduling:

```
* * * * * cd /var/www/html/crunz-ui && ./crunz-ui.sh
```


