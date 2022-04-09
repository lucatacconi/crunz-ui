# Crunz-ui Docker install example

Here is the procedure for activating Crunz-ui with the use of Crunz embedded in a new **php:7.4-apache** Docker container.

Update of system packages and installation of net-tools to have tools like ifconfig and others available:

```
sudo apt-get update
sudo apt-get upgrade
sudo apt install net-tools
```

Install Docker and check the status of the docker service once installed

```
sudo apt-get install docker.io

sudo service docker status
docker -v

```

Copy the Dockerfile and the files needed to configure Crunz-ui into the working directory

- [Dockerfile](dockerConf/Dockerfile) - Docker container configuration file
- [Crunz.yml](dockerConf/crunz.yml) - Crunz configuration

```
wget https://raw.githubusercontent.com/lucatacconi/crunz-ui/master/.github/dockerConf/Dockerfile
wget https://raw.githubusercontent.com/lucatacconi/crunz-ui/master/.github/dockerConf/crunz.yml

```

Construction of the docker container. Replace the example timezone with the correct one.

```
sudo docker build -t crunz-ui --build-arg TIMEZONE=Europe/Rome .

#If you want to build the development version
sudo docker build -t crunz-ui --build-arg TIMEZONE=Europe/Rome --build-arg VERSION=":dev-devel" .

```

Start Crunz-iu Docker container indicating the port through which to access the web interface (ex. DEST_PORT:ORIG_PORT 80:80 ):

```
sudo docker run -dp 80:80 --name crunz-ui-app crunz-ui

```

Identify the IP address of the host and use it to access the application.

```
ifconfig
```

Use the ip address retrived in your browser (The initial password for the **admin** user is **password** ):

```
http://IP_ADDRESS_RETRIVED/crunz-ui
```

Below is a list of commands useful for docker management

```

#Access to the bash inside the container
sudo docker exec -it crunz-ui-app bash

#Show al docker container running
sudo docker ps -a

#Show all docker images
sudo docker images

#Erasing of a Docker image
sudo docker image rm crunz-ui

#Start, stop delete a Docker container
sudo docker rm crunz-ui-app
sudo docker stop crunz-ui-app
sudo docker start crunz-ui-app

```

That's all. Have a good time :blush:
