# Proxmox

A lot of people use [Proxmox](https://www.proxmox.com/en/) to drive their self-hosted environment.
This guide will help you set up Statistics for Strava on a Proxmox virtual machine.

> [!IMPORTANT]
> Thanks to <a href="https://github.com/apachelance" target="_blank">apachelance</a> for creating this guide and sharing it with the community.

> [!NOTE]
> **Note** Depending on your docker installation or plugin, you may need to use either _docker compose_ or _docker-compose_.

### Create a privileged Proxmox container using the UI

* 1 CPU, 512 MB RAM, 8-10GB storage, fixed IP4 address
* Add root credentials
* Under Templates, choose Debian OS

### Install Docker in the container

open the container console, login as `root` and execute:

```bash
apt update && apt upgrade -y
apt install docker.io docker-compose -y
systemctl enable docker
```

### Create a directory

```bash
mkdir -p /opt/statistics-for-strava
cd /opt/statistics-for-strava
```

Now follow the instructions on the [installation page](/getting-started/installation.md) to set up Statistics for Strava.

### Permissions

Stop the container in your Proxmox UI and open the console of the proxmox host (not the Statistics for Strava container!). 
Now you need to modify the container configuration file. 
Please choose the file according to your Proxmox container ID (e.g. `110.conf` or `104.conf`)

```bash
nano /etc/pve/lxc/110.conf
```

Add these lines to the end of the file:

```
lxc.apparmor.profile: unconfined
lxc.cap.drop:
lxc.cgroup2.devices.allow: a
lxc.mount.auto: proc:rw sys:rw
lxc.mount.entry: /dev/fuse dev/fuse none bind,create=file
lxc.apparmor.allow_nesting: 1
```

### Restart the container

* Start your Statistics-for-Strava container using the Proxmox GUI
* Enter the console of the container and start docker

```bash
docker-compose up -d
```

### Strava API integration

Now follow the instructions on the [prerequisites page](/getting-started/prerequisites.md) to create the API keys/secret on the Strava website 
and add the keys to your .env file in your Statistics-for-strava directory using `nano .env`

Then recreate the container:

```bash
docker-compose up -d --force-recreate
```

### Final steps

* Open the URL auf your container (IP:8080), choose "Connect with Strava".
* A new refresh token will be generated
* Add it to the .env file in your container using `nano .env`
* Restart your container:

```bash
docker-compose stop
docker-compose start
```

> [!TIP]
> You're all set :partying_face:! You can now <a href="/#/getting-started/installation?id=import-and-build-statistics">import and build</a> your statistics.