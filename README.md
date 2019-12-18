# Crunz-Ui

Crunz-Ui starts from the most famous GitHub [Lavary/Crunz](https://github.com/lavary/crunz) project and is proposed as its graphical interface, to make its use more accessible and easy to use. Designed to be extremely light, it uses [lucatacconi/silly-vue-scaffolding](https://github.com/lucatacconi/silly-vue-scaffolding) which guarantees its elastic and dynamic structure.

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

Crunz-Ui natively uses Crunz libraries and functions to read and interpret the configured tasks.

Then it presents tasks in a tabular and graphical display.



## System Requirements

* Web server with URL rewriting
* PHP 7.1.3 or newer
* Composer


## Installation and application setup

It's recommended that you use [Composer](https://getcomposer.org/) to install Crunz-ui.

Access the **Document Root** folder on your **Apache Server** or one of the configured virtual hosts and run the following command:
```
bash
$ composer create-project lucatacconi/crunz-ui
```

Configure the main application information and environment by editing /config/application.json and .env file

In any case it is possible to download the complete package from Github and proceed with the configuration of the appropriate files.



## Usage on a prevus installation of Crunz


## Never use Crunz before


Download project
Configure .env
Check permission and in case update permission
Create log directory if non present
Copy .sh in crunz directory
Change crunz event in crontab
First login



## First Login

The application is preconfigured with a single access user to verify the login procedure and access the dashboard and the main menu.

To test access use the login **admin** and password **password**


## Roadmap

## Credits

* [Luca Tacconi](https://github.com/lucatacconi)
* [Emanuele Marchesotti](https://github.com/flagellarmirror)


## License

Silly Vue Scaffolding is licensed under the MIT license. See [License File](LICENSE.md) for more information.

