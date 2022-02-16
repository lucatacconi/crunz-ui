# Roadmap

## Upcoming/Desiderata implementations

* Vue-cli scaffolding conversion


## Release history

### v.2.7.5 (2022-02-28)

Crunz-ui updated stable release:
* Removed all non-open icons migrated to material design
* Implementation of Crunz [Lavary/Crunz](https://github.com/lavary/crunz) v3.2.1 in Crunz-ui
* Light and dark theme switch
* Introduction of a new section for the management of archived tasks (viewing, editing, deletion, recovery)
* Introduction of a new section that allows for the massive verification of the syntax of the tasks loaded on the system
* Introduction of an advanced search tool to Tasks' execution outcome list section. It is now possible to search for the results of specific tasks on dates and times of interest
* Updated documentation for installation and configuration
* Improvements in interface ergonomics
* Bugfix and code cleanup

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
* Implementation of Crunz [Lavary/Crunz](https://github.com/lavary/crunz) v3.0.1 in Crunz-ui

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
