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
wget https://github.com/lucatacconi/crunz-ui/blob/master/.github/dockerConf/Dockerfile
wget https://github.com/lucatacconi/crunz-ui/blob/master/.github/dockerConf/crunz.yml

```






sudo docker build -t crunz-ui --build-arg TIMEZONE=Europe/Rome .

sudo docker run -dp 80:80 --name crunz-ui-app crunz-ui


docker exec -it <container> bash







