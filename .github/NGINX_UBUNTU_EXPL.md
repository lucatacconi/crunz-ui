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
