# Crunz-ui

[![Latest Stable Version](https://poser.pugx.org/lucatacconi/crunz-ui/v/stable)](https://packagist.org/packages/lucatacconi/crunz-ui)
[![Total Downloads](https://poser.pugx.org/lucatacconi/crunz-ui/downloads)](https://packagist.org/packages/lucatacconi/crunz-ui)
[![Latest Unstable Version](https://poser.pugx.org/lucatacconi/crunz-ui/v/unstable)](https://packagist.org/packages/lucatacconi/crunz-ui)
[![License](https://poser.pugx.org/lucatacconi/crunz-ui/license)](https://packagist.org/packages/lucatacconi/crunz-ui)

Crunz-ui starts from the most famous GitHub [Lavary/Crunz](https://github.com/lavary/crunz) project and is proposed as its natural graphical interface, optimal to make its usage more accessible and easy to use. Designed to be extremely light, it uses [lucatacconi/silly-vue-scaffolding](https://github.com/lucatacconi/silly-vue-scaffolding) which guarantees to the project its elastic and dynamic structure.


## What Crunz is and how Crunz-ui connects to it

Crunz is an application that allows user to schedule tasks natively written in PHP, programming dates and time of start, interval of execution and conditions of execution and init.

You can find details on Crunz and how to write tasks to the following address: https://github.com/lavary/crunz

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

| ![Chrome](https://raw.github.com/alrra/browser-logos/master/src/chrome/chrome_48x48.png) | ![Firefox](https://raw.github.com/alrra/browser-logos/master/src/firefox/firefox_48x48.png) | ![Safari](https://raw.github.com/alrra/browser-logos/master/src/safari/safari_48x48.png) | ![Opera](https://raw.github.com/alrra/browser-logos/master/src/opera/opera_48x48.png) | ![Edge](https://raw.github.com/alrra/browser-logos/master/src/edge/edge_48x48.png) | ![IE](https://github.com/alrra/browser-logos/blob/master/src/archive/internet-explorer_9-11/internet-explorer_9-11_48x48.png) |
| ------------- | ------------- | ------------- | ------------- | ------------- | ------------- |
| Latest ✔ | Latest ✔ | Latest ✔  | Latest ✔  | Latest ✔  | **No** |


## System Requirements

* Linux OS and Bash shell
* Apache and PHP 7.1.3 or newer
* Composer


## Installation and application setup

It's recommended that you use [Composer](https://getcomposer.org/) to install Crunz-ui.

Start from your **Apache Server**'s **Document Root** folder or start from directory combined with one of the configured virtual hosts and type the following command:
```
composer require lucatacconi/crunz-ui
```
This will install Crunz-ui and all required dependencies.

Cruz-ui can be installed in two ways: it can work using the Crunz embedded in the packages or using the tasks and configurations of Crunz previously installed on the user's system.

If you have never used Crunz before or want to use the Crunz integrated in the packages, refer to the section [Never used Crunz before](#Never-used-Crunz-before).
If you want to use Cruz-ui on a version of Crunz previously installed on the user's systems, refer to the section [Usage on a previous installation of Crunz](#Usage-on-a-previous-installation-of-Crunz).


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

By default the configured Log folder is **./var/logs** inside Crunz-ui folder. To user custom Log folder configure the **.env** file with the absolute path of new Log folder.
The folder must be accessible and writeable by the Apache user.

If you have configured a custom log folder, the crontab configuration must be changed as follows:
```
* * * * * cd /[BASE_CRUNZUI_PATH] && ./crunz-ui.sh -l [LOGS_PATH]
```

Complete the configuration by setting the folders that act as containers for the tasks.
Refer to [Configuration of the task's folder structure](#Configuration-of-the-task's-folder-structure) section to configure the structure.


### Usage on a previous installation of Crunz

First of all you need to tell Crunz-ui the exact location where Crunz is installed.
To do this, edit the **.env** file inside the main folder of Crunz-ui by un-commenting the entry **CRUNZ_BASE_DIR** and indicating into that the value of the absolute path of the Crunz installation. In order to be able to insert, modify and delete tasks, the Apache user must have access and write permissions to the tasks folder.



Then configure the **.env** file with the absolute path of the Log folder. The folder must be accessible and writeable by the Apache user. By default the configured Log folder is **./var/logs** inside Crunz / Crunz-ui folder. To use the standard Log folder, inside the Crunz folder create the var/logs folder and set the write permissions to make them accessible to the Apache user.

Copy crunz-ui.sh file into the Crunz base folder:
```
* * * * * cd /[BASE_CRUNZUI_PATH]/crunz-ui.sh /[BASE_CRUNZ_PATH]
```

If you will use custom Log folder, create Log folder and check accessibility and write permissions to it:
```
cd /[BASE_CRUNZ_PATH]
mkdir ./var ./var/logs
```

Modify the Crunz process, configured in Crontab during the Crunz installation, replacing it with the Crunz-ui process:
```
* * * * * cd /[BASE_CRUNZ_PATH] && ./crunz-ui.sh
```

If you have configured a custom log folder, the crontab configuration must be changed as follows:
```
* * * * * cd /[BASE_CRUNZ_PATH] && ./crunz-ui.sh -l [LOGS_PATH]
```

At this point it is necessary to configure all the users who must be able to access the application.
Refer to [Accounts configuration](#Accounts-configuration) section to configure users. By default, in the basic configuration, the **admin** user is configured with the temporary password **password**.

Complete the configuration by setting the folders that act as containers for the tasks.
Refer to [Configuration of the task's folder structure](#Configuration-of-the-task's-folder-structure) section to configure the structure.


## Custom Log directory configuration

By default the configured Log folder is **./var/logs** inside Crunz / Crunz-ui folder. The folder must be accessible and writeable by the Apache user.

If you have configured a custom Log folder, the crontab configuration must be changed as follows:
```
* * * * * cd /[BASE_CRUNZ_PATH / BASE_CRUNZUI_PATH] && ./crunz-ui.sh -l [LOGS_PATH]
```

## Accounts configuration

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


## First Login

The application is preconfigured with a single access user to verify the login procedure and access the dashboard and the main menu.

To test access use the login **admin** and password **password**


## Configuration of the task's folder structure

The structure of folders is configured in the configuration file **/config/task_groups.json**.


```
{
    "subdir":"/",
    "description":"Main task",
    "children":[
        {
            "subdir":"/group1",
            "description":"Group 1",
            "disabled": false,
            "children":[
                {
                    "subdir":"/group1/subGroup1",
                    "description":"SubGroup 1",
                    "disabled": false
                },
                ...
            ]
        },
        {
            "subdir":"/group2",
            "description":"Group 2",
            "disabled": false
        },
        {
            "subdir":"/group3",
            "description":"Group 3",
            "disabled": true
        },
        ...
    ]
}

```


## Contributing

Please see [Contributing informations](CONTRIBUTING.md) for details.


## Roadmap

Please see [Roadmap](ROADMAP.md) for details.


## Credits

* [Luca Tacconi](https://github.com/lucatacconi)
* [Emanuele Marchesotti](https://github.com/flagellarmirror)


## License

Crunz-ui is licensed under the MIT license. See [License File](LICENSE.md) for more information.
