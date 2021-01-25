# Roadmap

## Upcoming implementations


## Release history

### v.2.0.0 (2021-01-XX)

Crunz-ui update stable release:
* Slim 4 library integration. Crunz-ui version 1 used version 3 of the Slim library
* Deploy of the new interface that allows loading of new tasks directly from the web interface through the embedded php editor
* Advanced management of high-frequency tasks (HFT), tasks that are carried out many times per hour. Previous version of Crunz-ui interface failed to display HFT crashing dashboard, daily and monthly view monthly view
* Environment Verification Panel Update. Additional controls and useful messages have been added
* Introduction of the event unique id which allows to uniquely identify the single task to be performed, improving log production operations
* New default task testAlive that can be activated and used to notify the server's IP on Internet twice a day via email
* Several improvements and bugfixes on all the code

### v.1.0.0 (2020-04-11)

Crunz-ui first stable release:
* Integrate Crunz library and funtions
* Tabular, monthly or weekly interface to view the scheduled and executed tasks
* Quick display of the execution result of the tasks that have been executed (Indicator icons easily show the result)
* Upload, download, edit or delete tasks
* Forced run of the task, even outside the scheduled time with eventual display of the log once the execution is completed
* It can be used with integrated Crunz or with a version of Crunz already installed on the system
