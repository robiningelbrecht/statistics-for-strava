# Scheduling

Your data only updates when the import and build commands are run.
If you have configured the [daemon](https://statistics-for-strava-docs.robiningelbrecht.be/#/getting-started/installation?id=docker-composeyml) container, these commands will run automatically.
Alternatively, you can implement your own mechanism to perform these updates without relying on the built-in daemon container.

## Using the built-in crontab on your host system

You can use the built-in crontab on your host system to run the import and build commands at regular intervals.
To do this, you need to add a new cron job to your crontab:

```bash
> crontab -e
```

### Example

```bash
> 0 19 * * * cd /path/to/compose.yaml && docker compose exec app bin/console app:strava:import-data && docker compose exec app bin/console app:strava:build-files
```

```bash
# Example of job definition:
# .---------------- minute (0 - 59)
# |  .------------- hour (0 - 23)
# |  |  .---------- day of month (1 - 31)
# |  |  |  .------- month (1 - 12) OR jan,feb,mar,apr ...
# |  |  |  |  .---- day of week (0 - 6) (Sunday=0 or 7) OR sun,mon,tue,wed,thu,fri,sat
# |  |  |  |  |
# *  *  *  *  * user-name command to be executed
```

## Using a Docker cron container

If you have no access to the host system's crontab,
you can use a Docker cron container to run the import and build commands at regular intervals.

### Ofelia

https://github.com/mcuadros/ofelia

There are some problems with Ofelia when chaining commands. To get around this, a shell script can be used.
Create a file called `refresh.sh` (or a name of your choosing) with the contents shown below:

```bash
#!/bin/sh
bin/console app:strava:import-data
bin/console app:strava:build-files
```

Edit `docker-compose.yml` to include the shell script as well as the Ofelia image.
Make sure the path to the shell script matches its location on your system.

```yml
services:
  app:
    image: robiningelbrecht/strava-statistics:latest
    volumes:
      - ./refresh.sh:/bin/refresh.sh
      - # ... other volumes
    # ... other configuration options
    labels:
      # refresh SFS daily at 7pm (1900). NB: ofelia includes seconds in the cron job 
      ofelia.enabled: "true"
      ofelia.job-exec.datecron.schedule: "0 0 19 * * *"
      ofelia.job-exec.datecron.command: "sh /bin/refresh.sh"
      
  ofelia:
    image: mcuadros/ofelia:latest
    depends_on:
      - app
    command: daemon --docker
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
```

### Deck Chores

> A job scheduler for Docker containers, configured via container labels.

https://github.com/funkyfuture/deck-chores

### Docker Cron

If you want to have more control over the scheduling, check out [this repository](https://github.com/AnalogJ/docker-cron).
It contains cron base images for various distros.

## Using Synology NAS task scheduler

If you are running the app on a Synology NAS,
you can use the Task Scheduler to run the import and build commands at regular intervals.

1. Open Control Panel -> Task Scheduler
2. Then Create -> Scheduled Task -> User Defined Script.
3. Set the name to `SFS Sync` and define the User as root. Ensure it is enabled.
4. On the schedule, choose the desired frequency.
5. In the Task Settings, in the Run command textbox, enter:

```bash
docker exec statistics-for-strava bin/console app:strava:import-data && docker exec statistics-for-strava bin/console app:strava:build-files
```

<div class="alert important">
Make sure to replace the "statistics-for-strava" with the container name you have defined in the container.
</div>
