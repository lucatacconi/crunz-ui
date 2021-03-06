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
sudo timedatectl set-timezone Europe/Rome
sudo service ntp restart
timedatectl
```
I.e.: sudo timedatectl set-timezone Europe/Rome

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

Installation of Crunz-ui and permissions configuration:
```
cd /var/www/html
sudo composer create-project lucatacconi/crunz-ui
sudo chown -R www-data:www-data crunz-ui
```

Configuration of Crunz time zone:
```
sudo ./vendor/bin/crunz publish:config
```

Configuration of crontab (crontab -e) adding crunz-ui service execution scheduling:

```
* * * * * cd /var/www/html/crunz-ui && ./crunz-ui.sh
```


