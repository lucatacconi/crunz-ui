# FAQ and Troubleshooting

Below are a number of useful tips for configuring the system and for solving common problems. If the problem you encountered is not reported please contact us and open an issue.


## Table of Contents
- [In the initial check on the dashboard I am reported configuration errors](#in-the-initial-check-on-the-dashboard-i-am-reported-configuration-errors)
- [I use Xampp on my server and I get an error when I try to manually execute a task](#i-use-xampp-on-my-server-and-i-get-an-error-when-i-try-to-manually-execute-a-task)
- [How do I configure the system to generate logs in a custom folder?](#how-do-i-configure-the-system-to-generate-logs-in-a-custom-folder)
- [My server is very slow. I can do something to make the interfaces more responsive?](#my-server-is-very-slow-i-can-do-something-to-make-the-interfaces-more-responsive)
- [I already have Crunz installed on my server. How do I configure Crunz-ui?](#i-already-have-crunz-installed-on-my-server-how-do-i-configure-crunz-ui)
- [I don't know the password to access the system](#i-dont-know-the-password-to-access-the-system)
- [After successful login the system returns to the login page](#after-successful-login-the-system-returns-to-the-login-page)


## In the initial check on the dashboard I am reported configuration errors

When accessing the Crunz-ui dashboard, Crunz-ui checks the system status. Verify that tasks and logs folder is present and writable. Then check that the Crunz configuration file is present and correctly configured.

In the event of an error relating to the log and task folders, the system requests that the folders be present and writable.
For example with apache server on Ubuntu for example configure folders with user and group www-data:
```
cd /var/www/html
sudo chown -R www-data:www-data crunz-ui
```

If Crunz is not configured, run the initial configuration batch (the procedure will produce the configuration file crunz.yml).
```
cd /var/www/html/crunz-ui
sudo ./vendor/bin/crunz publish:config
```

> :warning: ***Attention, the above examples work on an embedded Crunz installation***


## I use Xampp on my server and I get an error when I try to manually execute a task by interface

Crunz-ui allows you to launch the execution of tasks directly from the interface.

Using Crunz-ui on Xampp with PHP already present on the server in a separate installation from XAMPP, functions "Execute and wait log" present in the Tasks table section menu will fail with errors similar to the following: Unable to load dynamic library 'curl.so'.

In the situation above, Web interface in fact is served by Xampp Apache and PHP but scheduled task execution is executed by PHP installed in the machine. When you try to start execution by web interface Xampp try to use PHP installed on the same machine (usually present under /usr/bin) with modules and libraries installed in Xampp end obviously get an error.

You can avoid error replacing the php symlink in the /usr/bin directory and pointing it to Xampp's php.

> :warning: ***Do not delete the original symbolic link but save or rename it***
```
cd /usr/bin/
sudo mv /usr/bin/php /usr/bin/php-old
sudo ln -s /opt/lampp/bin/php /usr/bin/
```


## How do I configure the system to generate logs in a custom folder?

By default crunz.ui.sh batch will search for standard log folder ./var/logs inside main Crunz.ui folder.

if you want to configure a custom log directory first indicate the location of the custom log folder inside the .env file:
```
JWT_SECRET = "234sjdflajsfajsfagwq1239fwqeff7sdf32ghdsf67048qo4"

SESSION_DURATION = "2 hours"

#Absolute path to Crunz base directory. Leave empty if you want to use Crunz embedded
#CRUNZ_BASE_DIR = ""

LOGS_DIR = "./var/logs" #<--- MODIFY HERE

RUN_MODE = "PRODU" #PRODU | DEVEL

#Important notice
#In the case of servers with less computing power, checking the syntax of the tasks considerably slows down the display of tables and statistics
#you can set the parameter CHECK_PHP_TASKS_SYNTAX to false to inhibit syntax checking
#Configuring the parameter CHECK_PHP_TASKS_SYNTAX to false in case of syntax errors in the tasks could cause anomalous behavior in the Crunz-ui interfaces

CHECK_PHP_TASKS_SYNTAX = true #true | false
```

Then modify standard Crunz-ui event configuration in the crontab from:
```
* * * * * cd /[BASE_CRUNZUI_PATH] && ./crunz-ui.sh
```

to (replace BASE_CRUNZUI_PATH and LOGS_PATH with custom paths configured on your system):
```
* * * * * cd /[BASE_CRUNZUI_PATH] && ./crunz-ui.sh -l [LOGS_PATH]
```


## My server is very slow. I can do something to make the interfaces more responsive?

Crunz-ui checks the syntax of tasks during all read, update or write operations.

In the case of servers with less computing power, checking the syntax of the tasks considerably slows down the display of tables and statistics.

You can set the parameter CHECK_PHP_TASKS_SYNTAX to false to inhibit syntax checking.

> :warning: ***Configuring the parameter CHECK_PHP_TASKS_SYNTAX to false in case of syntax errors in the tasks could cause anomalous behavior in the Crunz-ui interfaces***


## I already have Crunz installed on my server. How do I configure Crunz-ui?

Cruz-ui has Crunz packages in its libraries.

However Crunz-ui can use Crunz installations already installed on your server.

Please refer to [Readme](README.md#usage-on-a-previous-installation-of-crunz)


## I don't know the password to access the system

The application is preconfigured with a single access user to verify the login procedure and access the dashboard and the main menu.

To test access use the login **admin** and password **password**

All users enabled to access the application are configured in the configuration file **/config/accounts.json**.


## After successful login the system returns to the login page

The problem occurs when there is an error in the timezone configuration or in the configuration of the current date and time.

It is important that the system clock is correctly synchronized. In case of unsynchronized clock there could be misalignments in the execution of the tasks or in the management of the user sessions.

First of all check the configuration present in the crunz.yml file.

Then check the date and time configured on your server:
```
date
```

It is advisable to use npm to keep the server time synchronized:
```
sudo apt-get install ntp
sudo timedatectl set-timezone TIMEZONE
sudo service ntp restart
timedatectl
```

Replace TIMEZONE with the desired time zone. Refer to [Timezone Database](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones) for values.
