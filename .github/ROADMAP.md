# Roadmap

## Upcoming/Desiderata implementations

* Nuxt framework conversion
* Vue 3 migration
* Vuetify 3 migration
* Log compression
* Data tank in json format to be used within the tasks

## Release history

### v.2.7.24 (2024-04-27)

Crunz-ui updated stable release:
* New section for checking log occupancy directly from the interface with the possibility of removing older logs. Functionality enabled only for admin users
* Display of free space on the log partition and estimate of the time remaining until saturation
* New system task to notify via email of running out of space on the log partition
* Instruction for installation and configuration on Nginx server
* New functionality for move o rename an task in Tasks Table
* Refactoring of the task reading and management engine
* PHP 8.3 compatibility fix
* JWT Authentication Middleware update to other library (JimTools/jwt-auth)
* Acquisition of new Crunz library updates and functions
* Several improvements, bug fixes and and code cleanup throughout the application
* Faq and troubleshooting page updated
* Updated documentation for installation and configuration


### v.2.7.10 (2023-05-19)

Crunz-ui updated stable release:
* Tools api accessible via basic authentication
* PHP 8.1 compatibility fix
* Library version update
* Bug fixes and and code cleanup


### v.2.7.9 (2023-02-17)

Crunz-ui updated stable release:
* Implementation of [Crunzphp/Crunz](https://github.com/crunzphp/crunz) v3.4.1 in Crunz-ui
* New section for the management of users authorized to access. Functionality enabled only for admin users
* New section for managing Crunz configuration directly from the interface. Functionality enabled only for admin users
* New section for analyzing tasks operation, system load, logs occupation and more
* Reorganization of Tasks' execution outcome list section to improve its ergonomics
* New functionality for cloning an already existing task in Tasks Table
* Refactoring of the task reading and management engine
* Statistics engine refactoring
* Advanced search: it is now possible to concatenate search parameters with the + or select whether to carry out searches with case sensitive or not
* Several improvements, bug fixes and and code cleanup throughout the application
* Happy Italian Carnival Day

**Advanced search tool refactored:**
![Advanced search tool refactored](https://user-images.githubusercontent.com/9921890/219686300-5126d524-eab4-4a5f-b4a1-ff3b37effa0d.png)

**Analytics and statistics:**
![Analytics and statistics](https://user-images.githubusercontent.com/9921890/219686347-d9aa244f-6a3d-420a-bdf7-8f47dd54a7c1.png)


### v.2.7.5 (2022-04-25)

Crunz-ui updated stable release:
* **Dockerfile for setup of a Doker container complete with Crunz and Crunz-ui configured and ready to use**
* Complete documentation on the installation and startup procedure via Docker
* Implementation of [Crunzphp/Crunz](https://github.com/crunzphp/crunz) v3.2.1 in Crunz-ui
* Light and dark theme switch
* Zoom control to have a better view of the tasks in  Daily calendar section
* Introduction of a new section for the management of archived tasks (viewing, editing, deletion, recovery)
* Introduction of a new section that allows for the massive verification of the syntax of the tasks loaded on the system
* Introduction of an advanced search tool to Tasks' execution outcome list section. It is now possible to search for the results of specific tasks on dates and times of interest
* Updated documentation for installation and configuration
* Removed Swift mailer using Simphony mailer instead for [Crunzphp/Crunz](https://github.com/crunzphp/crunz) consistency
* Improvements in interface ergonomics
* System icons uniformed to material design icon
* Several improvements, bug fixes and and code cleanup throughout the application
* Happy Italian Liberation Day
<!-- * Demo environment for testing the application -->

![Advanced search tool](https://user-images.githubusercontent.com/9921890/154231756-d85229e6-5de5-44c6-893f-fdc8c6ecefe7.png)


### v.2.6.6 (2021-12-31)

Crunz-ui updated stable release:
* Security update
* Happy new year

### v.2.6.1 (2021-09-02)

Crunz-ui updated stable release:
* Optimization of the task reading engine that now presents results faster and more efficiently
* Enabled, in the monthly and week task display interfaces, the possibility of viewing the execution logs also of executions prior to the last one.
* New section for viewing the history of task executions (allows you to access the log of individual executions, see the outcome and compare it with the log of the last execution)
* Multiple task upload in the task upload interface
* Larger log viewer and task editing screens to enhance user operations
* More understandable and meaningful labels
* Improvements in interface ergonomics
* Several improvements and bug fixes on all the code
* Fixes issue #9, #10 and #11
* Disable via parameter of the task syntax check, an operation that can slow down a lot of obsolete servers
* Introduction of the faq and troubleshooting page
* Updated documentation for installation and configuration
* Implementation of Crunz [Crunzphp/Crunz](https://github.com/crunzphp/crunz) v3.0.1 in Crunz-ui

### v.2.0.0 (2021-01-26)

Crunz-ui updated stable release:
* Slim 4 library integration. Crunz-ui version 1 used version 3 of the Slim library
* Deploy of the new interface that allows loading of new tasks directly from the web interface through the embedded php editor
* Advanced management of high-frequency tasks (HFT), tasks that are carried out many times per hour. Previous version of Crunz-ui interface failed to display HFT crashing dashboard, daily and monthly view monthly view
* Environment Verification Panel Update. Additional controls and useful messages have been added
* Introduction of the event unique id which allows to uniquely identify the single task to be performed, improving log production operations
* More accurate analysis of tasks execution and reporting, in the Task Table section, of any scheduled but not executed tasks
* New default task testAlive that can be activated and used to notify the server's IP on Internet twice a day via email
* Several improvements and bug fixes on all the code

### v.1.0.0 (2020-04-11)

Crunz-ui first stable release:
* Integrate Crunz library and functions
* Tabular, monthly or weekly interface to view the scheduled and executed tasks
* Quick display of the execution result of the tasks that have been executed (Indicator icons easily show the result)
* Upload, download, edit or delete tasks
* Forced run of the task, even outside the scheduled time with eventual display of the log once the execution is completed
* It can be used with integrated Crunz or with a version of Crunz already installed on the system
