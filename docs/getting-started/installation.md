# Installation

<div class="alert info">
    Make sure to read the <a href="/#/getting-started/prerequisites">prerequisites</a> before you start installing the app.
</div>

Start off by showing some :heart: and give this repo a star. Then from your command line:

```bash
# Create a new directory
> mkdir statistics-for-strava
> cd statistics-for-strava

# Create docker-compose.yml and copy the example contents into it
> touch docker-compose.yml
> nano docker-compose.yml

# Create .env and copy the example contents into it. Configure as you see fit
> touch .env
> nano .env

# Create config.yaml and copy the example contents into it. Configure as you see fit
> touch config/config.yaml
> nano config/config.yaml
```

## docker-compose.yml

```yml
services:
  app:
    image: robiningelbrecht/strava-statistics:latest
    container_name: statistics-for-strava
    restart: unless-stopped
    volumes:
      - ./config:/var/www/config/app
      - ./build:/var/www/build
      - ./storage/database:/var/www/storage/database
      - ./storage/files:/var/www/storage/files
    env_file: ./.env
    ports:
      - 8080:8080
```

## .env

<div class="alert important">
    Every time you change the .env file, you need to recreate (for example; docker compose up -d) your container for the changes to take effect (restarting does not update the .env).
</div>

```bash
# The client id of your Strava app.
STRAVA_CLIENT_ID=YOUR_CLIENT_ID
# The client secret of your Strava app.
STRAVA_CLIENT_SECRET=YOUR_CLIENT_SECRET
# You will need to obtain this token the first time you launch the app. 
# Leave this unchanged for now until the app tells you otherwise.
# Do not use the refresh token displayed on your Strava API settings page, it will not work.
STRAVA_REFRESH_TOKEN=YOUR_REFRESH_TOKEN_OBTAINED_AFTER_AUTH_FLOW
# Valid timezones can found under TZ Identifier column here: https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List
TZ=Etc/GMT

# The UID and GID to create/own files managed by statistics-for-strava
#PUID=
#PGID=
```

## config.yaml

[include](../configuration/config-yaml-example.md ':include')

### Running the application

To run the application run the following command:

```bash
> docker compose up
```

The docker container is now running; navigate to `http://localhost:8080/` to access the app.

## Obtaining a Strava refresh token

<div class="alert danger">
Do <strong>not</strong> use the refresh token displayed on your Strava API settings page, it will not work.
</div>

The first time you launch the app, you will need to obtain a `Strava refresh token`.
The app needs this token to be able to access your data and import it into your local database.

Navigate to http://localhost:8080/.
You should see this page—just follow the steps to complete the setup.

![Strava Authorization](../assets/images/strava-oauth.png)

## Import and build statistics

Once you have successfully authenticated with Strava, you can import your data and build the html files,
after which you can view your statistics.

```bash
> docker compose exec app bin/console app:strava:import-data
> docker compose exec app bin/console app:strava:build-files
```

<div class="alert important">
Everytime you import data, you need to rebuild the HTML files to see the changes. 
By default the import and build commands are run periodically based on the schedule defined in your <strong>.env</strong> file.
</div>

## Scheduling

By default, for your data to be updated, you need to run the import and build commands manually.
However, there are several ways to automate this process.

### Using the built-in crontab on your host system

You can use the built-in crontab on your host system to run the import and build commands at regular intervals.
To do this, you need to add a new cron job to your crontab:

```bash
> crontab -e
```

#### Example

```bash
> 0 19 * * * docker compose exec app bin/console app:strava:import-data && docker compose exec app bin/console app:strava:build-files
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

### Using a Docker cron container

If you have no access to the host system's crontab, 
you can use a Docker cron container to run the import and build commands at regular intervals.

* https://github.com/willfarrell/docker-crontab
* https://github.com/mcuadros/ofelia
