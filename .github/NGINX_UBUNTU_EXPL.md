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

Editing to php configuration file /etc/php/8.X/fpm/php.ini and /etc/php/8.X/cli/php.ini to set timezone and to increase the maximum execution time of the scripts. To select the correct maximum execution time, consider the time required to execute the longest task you want to schedule.

Modify the nginx site-available defaul configuration file to allow php execution and to set the crunz-ui directory:
```
vi /etc/nginx/sites-available/default
```

Add index.php to the index line:
```
index index.php index.html index.htm index.nginx-debian.html;
```
Deny access to Apache's .htaccess files:
```
location ~ /\.ht {
    deny all;
}
```

Enable route management for apis (Add at the end of the file):
```
location /crunz-ui/routes {
        alias /var/www/html/crunz-ui/routes;
        try_files $uri $uri/ @crunz-ui-routes;

        location ~ \.php$ {
                include snippets/fastcgi-php.conf;
                fastcgi_param SCRIPT_FILENAME $request_filename;
                fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        }
}

location @crunz-ui-routes {
            rewrite /crunz-ui/routes/(.*)$ /crunz-ui/routes/index.php?/$1 last;
}
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
