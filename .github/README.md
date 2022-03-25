# Crunz-ui

[![Latest Stable Version](https://poser.pugx.org/lucatacconi/crunz-ui/v/stable)](https://packagist.org/packages/lucatacconi/crunz-ui)
[![Total Downloads](https://poser.pugx.org/lucatacconi/crunz-ui/downloads)](https://packagist.org/packages/lucatacconi/crunz-ui)
[![Latest Unstable Version](https://poser.pugx.org/lucatacconi/crunz-ui/v/unstable)](https://packagist.org/packages/lucatacconi/crunz-ui)
[![License](https://poser.pugx.org/lucatacconi/crunz-ui/license)](https://packagist.org/packages/lucatacconi/crunz-ui)

![Crunz-ui](https://user-images.githubusercontent.com/9921890/105859101-6cf75080-5fec-11eb-9ede-0b6da2ec9898.png)
<br />
<br />
<br />

Crunz-ui starts from the most famous GitHub [Lavary/Crunz](https://github.com/lavary/crunz) project and it's proposed to be its natural graphical interface, optimal to make its usage more accessible and easy to use. Designed to be extremely light, it uses [lucatacconi/silly-vue-scaffolding](https://github.com/lucatacconi/silly-vue-scaffolding) which guarantees to the project its elastic and dynamic structure.

<details>
 <summary><strong>Table of Contents</strong> (click to expand)</summary>

- [Lazy Developer Shortcuts](LAZY_AREA.md)
- [What Crunz is and how Crunz-ui connects to it](#what-crunz-is-and-how-crunz-ui-connects-to-it)
- [What else can Crunz-ui do](#what-else-can-crunz-ui-do)
- [Browser Support](#browser-support)
- [System Requirements](#system-requirements)
- [Pre-installation safety warnings](#pre-installation-safety-warnings)
- [Installation and application setup](#installation-and-application-setup)
  - [Never used Crunz before](#never-used-crunz-before)
  - [Usage on a previous installation of Crunz](#usage-on-a-previous-installation-of-crunz)
  - [Custom log directory configuration](#custom-log-directory-configuration)
  - [Accounts configuration](#accounts-configuration)
  - [Ubuntu/Debian setup example](UBUNTU_EXPL.md)
- [First login](#first-login)
- [Contributing informations](CONTRIBUTING.md)
- [Roadmap](ROADMAP.md)
- [FAQ / Troubleshooting](FAQ.md)
</details>

## Lazy Developer Shortcuts

![Lazy Developer Shortcutsl](https://user-images.githubusercontent.com/9921890/160169471-292b008a-ce98-4c7a-9c5e-edf1ed11bd7e.png)

Shortcuts to Docker and Apache/PHP server installation instructions.

[Lazy Developer Shortcuts](LAZY_AREA.md)


## What Crunz is and how Crunz-ui connects to it
Crunz is an application that allows users to schedule tasks natively written in PHP, programming date and time of start, interval and conditions of execution and init.

You can find details about Crunz and how to write and schedule tasks to the following address: https://github.com/lavary/crunz

Crunz task example:
```php
<?php
// tasks/backupTasks.php

use Crunz\Schedule;

$schedule = new Schedule();
$task = $schedule->run('cp project project-bk');
$task->daily();

return $schedule;
```

Crunz-ui natively uses Crunz libraries and functions to read and interpret the configured tasks.
Then presents tasks in a tabular or graphical display, showing them on a monthly or daily view.


## What else can Crunz-ui do?

In addition to displaying tasks in tabular or graphic format, Crunz-ui allows you to:
* Load new tasks with an intuitive and simple file upload system
* Download or view the content of the task via interface
* Quick display of the execution result of the tasks that have been executed (Indicator icons easily show the result)
* Display of the execution log of the tasks performed using the appropriate interface
* Forced run of the task, even outside the scheduled time with eventual display of the log once the execution is completed

## Browser Support

| ![Chrome](https://github.com/alrra/browser-logos/blob/main/src/chrome/chrome_48x48.png) | ![Firefox](https://github.com/alrra/browser-logos/blob/main/src/firefox/firefox_48x48.png) | ![Safari](https://github.com/alrra/browser-logos/blob/main/src/safari/safari_48x48.png) | ![Opera](https://github.com/alrra/browser-logos/blob/main/src/opera/opera_48x48.png) | ![Edge](https://github.com/alrra/browser-logos/blob/main/src/edge/edge_48x48.png) | ![IE](https://github.com/alrra/browser-logos/blob/main/src/archive/internet-explorer-tile_10-11/internet-explorer-tile_10-11_48x48.png) |
| ------------- | ------------- | ------------- | ------------- | ------------- | ------------- |
| Latest ✔ | Latest ✔ | Latest ✔  | Latest ✔  | Latest ✔  | **No** |


## System Requirements

* Linux OS and Bash shell
* Service ntp enabled
* Apache and PHP 7.4 or newer, with rewrite.load module enabled
* Composer
* Sudo capabilities

## Pre-installation safety warnings

> :warning: **Crunz and Crunz-ui need the ability to set crontab to schedule the regular execution of task management process. Crontab entry owner must be carefully selected: using high permissions level user (ex root) could allow tasks to access sensitive files or perform malicious operations on the entire server file system.**

> :warning: **It is very important to change as soon as possible the default password of the Crunz-ui admin user. Leaving the default password could allow malicious users to access the Crunz-ui task manager and load dangerous tasks with the capability to access sensitive files or perform malicious operations on the entire server file system**

> :warning: **It is important that the system clock is correctly synchronized. In case of unsynchronized clock there could be misalignments in the execution of the tasks or in the management of the user sessions.**

> :warning: **For browser security configurations, Ccopy to clipboard buttons are available only if Crunz-ui is released in localhost or in an https domain**

## Installation and application setup

It's recommended that you use [Composer](https://getcomposer.org/) to install Crunz-ui.

Start from your **Apache Server**'s **Document Root** folder or start from a directory combined with one of the configured virtual hosts and type the following command:
```
composer create-project lucatacconi/crunz-ui
```
This will install Crunz-ui and all required dependencies.

There is no need to set a long time in PHP max_execution_time property in your php.ini: Crunz performs tasks as if they were run from the console. When running PHP from the command line the default setting is 0 therefore without time limits

Cruz-ui can be installed in two ways: it can work using the Crunz embedded in the packages or using the tasks and configurations of Crunz previously installed on the user's system.

If you have never used Crunz before or want to use the Crunz integrated into the packages, refer to the section [Never used Crunz before](#Never-used-Crunz-before).
If you want to use Cruz-ui on a version of Crunz previously installed on the user's systems, refer to the section [Usage on a previous installation of Crunz](#Usage-on-a-previous-installation-of-Crunz).

> :information_source: By default Crunz checks the correctness of the php code before considering the task file. In case of servers with less computing power, checking the syntax of the tasks considerably slows down the display of tables and statistics. You can set the .env parameter CHECK_PHP_TASKS_SYNTAX to false to inhibit syntax checking. In case of syntax errors in the tasks, configuring parameter CHECK_PHP_TASKS_SYNTAX to false could cause anomalous behavior in the Crunz-ui interfaces

Crunz-ui can also be used with Xampp. However, it is necessary to create a symbolic link of the Xampp's PHP in your system executables folder:
```
sudo ln -s /opt/lampp/bin/php /usr/bin/
```

Using Crunz-ui on Xampp with PHP already present on the server in a separate installation from XAMPP, functions "Execute and wait log" present in the Tasks table section menu will fail with the following error: Unable to load dynamic library 'curl.so'. Check [FAQ](FAQ.md) to solve the problem.

### Never used Crunz before

Cruz-ui has Crunz packages in its libraries. Once configured, it can then start viewing, managing and executing tasks.

To work in this mode, once Crunz-ui is installed, proceed as follows:

By accessing the project folder, we can use the specific function of Crunz to generate the basic configuration file of Crunz itself:
```
./vendor/bin/crunz publish:config
```

The procedure will ask the user to provide default timezone for task run date calculations; execution will generate the Crunz configuration file with the default settings.
For more advanced configurations, refer to the Crunz manual.

At this point it is necessary to configure all the users who must be able to access the application.
Refer to [Accounts configuration](#Accounts-configuration) section to configure users. By default, in the basic configuration, the **admin** user is configured with the temporary password **password**.

Then set an ordinary cron job (a crontab entry) which runs every minute, and delegates the responsibility to Crunz-ui event runner:
```
* * * * * cd /[BASE_CRUNZUI_PATH] && ./crunz-ui.sh
```

By default the configured log folder is **./var/logs** inside Crunz-ui folder. To use custom log folder configure the **.env** file with the path of new log folder.
The folder must be accessible and writeable by the Apache user.

If you have configured a custom log folder, the crontab configuration must be changed as follows:
```
* * * * * cd /[BASE_CRUNZUI_PATH] && ./crunz-ui.sh -l [LOGS_PATH]
```

Please refer to [Ubuntu/Debian setup example](UBUNTU_EXPL.md) for suggestion.


### Usage on a previous installation of Crunz

First of all you need to tell Crunz-ui the exact location where Crunz is installed.
To do this, edit the **.env** file inside the main folder of Crunz-ui by un-commenting the entry **CRUNZ_BASE_DIR** and indicating into that the value of the absolute path of Crunz installation. In order to be able to insert, modify and delete tasks, the Apache user must have access and write permissions to the tasks folder.

Then copy crunz-ui.sh file and TasksTreeReader.php into the Crunz base folder:
```
cp /[BASE_CRUNZUI_PATH]/crunz-ui.sh /[BASE_CRUNZ_PATH]
cp /[BASE_CRUNZUI_PATH]/TasksTreeReader.php /[BASE_CRUNZ_PATH]
```

By default **crunz.ui.sh** batch will search for standard log folder **./var/logs** inside main Crunz folder. Therefore, if you want to use standard configuration, you need to create default folder for logs. The folder must be accessible and writeable by the Apache user:
```
cd /[BASE_CRUNZ_PATH]
mkdir ./var ./var/logs
```

Modify the Crunz process, configured in Crontab during the Crunz installation, replacing it with the Crunz-ui process:
```
* * * * * cd /[BASE_CRUNZ_PATH] && ./crunz-ui.sh
```

Configure, if needed, Crunz-ui **.env** file with a custom path of the log folder. In this case too it is important that the folder is accessible and writeable by the Apache user.
If you have configured a custom log folder, the crontab configuration must be changed as follows:
```
* * * * * cd /[BASE_CRUNZ_PATH] && ./crunz-ui.sh -l [LOGS_PATH]
```

At this point it is necessary to configure all the users who must be able to access the application.
Refer to [Accounts configuration](#Accounts-configuration) section to configure users. By default, in the basic configuration, the **admin** user is configured with the temporary password **password**.


### Custom log directory configuration

By default, the configured log folder is **./var/logs** inside Crunz / Crunz-ui folder. The folder must be accessible and writeable by the Apache user.

To configure custom folder set the **.env** file with the path of new log folder.

If you have configured a custom log folder, the crontab configuration must be changed as follows:
```
* * * * * cd /[BASE_CRUNZ_PATH / BASE_CRUNZUI_PATH] && ./crunz-ui.sh -l [LOGS_PATH]
```


### Accounts configuration

All users enabled to access the application are configured in the configuration file **/config/accounts.json**.

The accounts.json configuration file has the following format:
```
[
    {
        "username":"admin",
        "name":"Admin User",
        "userType":"admin",
        "email":"admin@nomail.com",
        "password":"password",
        "active":"Y",
        "expireDate":"2020-10-10",
        "customSessionDuration":""
    },
    {
        "username":"j.doe",
        "name":"Jhon Doe",
        "userType":"user",
        "email":"j.doe@nomail.com",
        "password":"password",
        "active":"Y",
        "expireDate":"2020-10-10",
        "customSessionDuration":""
    },
    ...
]
```

Among the various information listed, the type of user is also induced, information that is then used to filter the menu items enabled for the user.
For simplicity's choice the access configurations have been inserted in a file. However, nothing prevents the implementation of user management based on database reading.


## First login

The application is preconfigured with a single access user to verify the login procedure and access the dashboard and the main menu.

To test access use the login **admin** and password **password**


## Contributing

This project is maintained by a group of awesome contributors. Contributions are extremely welcome :heart:.
Please see [Contributing informations](CONTRIBUTING.md) for details.


## Roadmap

Please see [Roadmap](ROADMAP.md) for details.


## FAQ and Troubleshooting

For help, FAQ or troubleshooting please refer to [FAQ and Troubleshooting](FAQ.md).


## Credits

* [Luca Tacconi](https://github.com/lucatacconi)
* [Emanuele Marchesotti](https://github.com/flagellarmirror)


## License

Crunz-ui is licensed under the MIT license. See [License File](LICENSE.md) for more information.
