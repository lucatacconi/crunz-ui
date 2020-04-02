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

| First Header  | Second Header |
| ------------- | ------------- |
| Content Cell  | Content Cell  |
| Content Cell  | Content Cell  |



| ![Chrome](https://raw.github.com/alrra/browser-logos/master/src/chrome/chrome_48x48.png) |
| ![Firefox](https://raw.github.com/alrra/browser-logos/master/src/firefox/firefox_48x48.png) |
| ![Safari](https://raw.github.com/alrra/browser-logos/master/src/safari/safari_48x48.png) |
| ![Opera](https://raw.github.com/alrra/browser-logos/master/src/opera/opera_48x48.png) |
| ![Edge](https://raw.github.com/alrra/browser-logos/master/src/edge/edge_48x48.png) |
| ![IE](https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Internet_Explorer_10_logo.svg/48px-Internet_Explorer_10_logo.svg.png) |
| --- | --- | --- | --- | --- | --- |
| Latest ✔ | Latest ✔ | Latest ✔ | Latest ✔ | Latest ✔ | **No** |


## System Requirements

* Linux OS
* Apache and PHP 7.1.3 or newer
* Composer


## Installation and application setup




## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Roadmap

Please see [ROADMAP](ROADMAP.md) for details.


## Credits

* [Luca Tacconi](https://github.com/lucatacconi)
* [Emanuele Marchesotti](https://github.com/flagellarmirror)


## License

Silly Vue Scaffolding is licensed under the MIT license. See [License File](LICENSE.md) for more information.


















## Installation and application setup

It's recommended that you use [Composer](https://getcomposer.org/) to install Crunz-ui.

Access the **Document Root** folder on your **Apache Server** or one of the configured virtual hosts and run the following command:
```
$ composer create-project lucatacconi/crunz-ui
```

Configure the main application information and environment by editing /config/application.json and .env file

In any case it is possible to download the complete package from Github and proceed with the configuration of the appropriate files.


## Never used Crunz before

Download project
Configure .env
Check permission and in case update permission
Create log directory if non present
Copy .sh in crunz directory
Change crunz event in crontab
First login



## Usage on a previous installation of Crunz








## First Login

The application is preconfigured with a single access user to verify the login procedure and access the dashboard and the main menu.

To test access use the login **admin** and password **password**



