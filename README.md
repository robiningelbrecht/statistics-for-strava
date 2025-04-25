<p align="center">
  <img src="public/assets/images/logo.svg" width="250" alt="Logo" >
</p>

<h1 align="center">Statistics for Strava</h1>

<p align="center">
<a href="https://github.com/robiningelbrecht/statistics-for-strava/actions/workflows/ci.yml"><img src="https://github.com/robiningelbrecht/statistics-for-strava/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
<a href="https://github.com/robiningelbrecht/statistics-for-strava/actions/workflows/docker-image.yml"><img src="https://github.com/robiningelbrecht/statistics-for-strava/actions/workflows/docker-image.yml/badge.svg" alt="Publish Docker image"></a>
<a href="https://raw.githubusercontent.com/robiningelbrecht/statistics-for-strava/refs/heads/master/LICENSE"><img src="https://img.shields.io/github/license/robiningelbrecht/statistics-for-strava?color=428f7e&logo=open%20source%20initiative&logoColor=white" alt="License"></a>
<a href="https://hub.docker.com/r/robiningelbrecht/strava-statistics"><img src="https://img.shields.io/docker/image-size/robiningelbrecht/strava-statistics" alt="Docker Image Size"></a>
<a href="https://hub.docker.com/r/robiningelbrecht/strava-statistics"><img src="https://img.shields.io/docker/pulls/robiningelbrecht/strava-statistics" alt="Docker pulls"></a>
<a href="https://hub.docker.com/r/robiningelbrecht/strava-statistics"><img src="https://img.shields.io/docker/v/robiningelbrecht/strava-statistics?sort=semver" alt="Docker version"></a>
<a href="https://discord.gg/p4zpZyCHNc"><img src="https://img.shields.io/badge/Statistics%20for%20Strava-%235865F4?logo=discord&logoColor=%23ffffff&label=%20&labelColor=585858" alt="Discord server"></a>  
</p>

---

<h4 align="center">Statistics for Strava is a self-hosted web app designed to provide you with better stats.</h4>

<p align="center">
  <a href="#-showcase">Showcase</a> •
  <a href="#%EF%B8%8F-disclaimer">Disclaimer</a> •
  <a href="#-wiki">Wiki</a> •
  <a href="#-prerequisites">Prerequisites</a> •
  <a href="#%EF%B8%8F-installation">Installation</a> •
  <a href="#%EF%B8%8Fimport-and-build-statistics">Import and build statistics</a> •
  <a href="#%EF%B8%8F-locales-and-translations">Locales and translations</a> •
  <a href="https://discord.gg/p4zpZyCHNc">Discord</a>
</p>

<p align="center">
  <a href="https://www.buymeacoffee.com/ingelbrecht" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 40px !important;" ></a>
</p>

## 📸 Showcase

> [!NOTE]
> This app is in no way affiliated with or part of the official Strava software suite.

https://github.com/user-attachments/assets/9aaaafd9-bc8f-4e1d-bb9d-45d3c661a080

### Key Features

* Dashboard with various stats and charts
* Detailed list of all your activities
* Monthly stats with calendar view
* Gear stats
* Gear (component) maintenance tracking ([instructions](https://github.com/robiningelbrecht/statistics-for-strava/wiki/Gear-maintenance))
* Eddington for biking and running activities
* Detailed list of your segments and corresponding efforts
* Heatmap
* Strava Rewind, a fun way to see your year in review
* History of completed Strava challenges
* History of activity photos
* User badges

## ⚠️ Disclaimer

* 📦 __Backup before updates__: Always backup your Docker volumes before upgrading.
* 🔄 __Stay up-to-date__: Make sure you're running the latest version for the best experience.
* 🤓 __Check the release notes__: Always check the release notes to verify if there are any breaking changes.

## 📚 Wiki

Read [the wiki](https://github.com/robiningelbrecht/statistics-for-strava/wiki) before opening new issues. The question you have might be answered over there.

## 🪄 Prerequisites

You'll need a `Strava client ID` and `Strava client Secret`

* Navigate to your [Strava API settings page](https://www.strava.com/settings/api).
* Copy the `client ID` and `client secret`, you'll need these during the [installation](#%EF%B8%8F-installation)

## 🛠️ Installation 

> [!NOTE]
> To run this application, you'll need [Docker](https://docs.docker.com/engine/install/) with [docker-compose](https://docs.docker.com/compose/install/).

Start off by showing some ❤️ and give this repo a star. Then from your command line:

```bash
# Create a new directory
> mkdir statistics-for-strava
> cd statistics-for-strava

# Create docker-compose.yml and copy the example contents into it
> touch docker-compose.yml
> nano docker-compose.yml

# Create .env and copy the example contents into it. Configure as you like
> touch .env
> nano .env
```

### docker-compose.yml

```yml
services:
  app:
    image: robiningelbrecht/strava-statistics:latest
    volumes:
      - ./build:/var/www/build
      - ./storage/database:/var/www/storage/database
      - ./storage/files:/var/www/storage/files
      - ./storage/gear-maintenance:/var/www/storage/gear-maintenance
    env_file: ./.env
    ports:
      - 8080:8080
```

### .env

> [!IMPORTANT]
> Every time you change the .env file, you need to restart your container for the changes to take effect.

```bash
# ⚠ ️Every time you change the .env file, you need to restart your container for the changes to take effect.

# The URL on which the app will be hosted. This URL will be used in the manifest file. 
# This will allow you to install the web app as a native app on your device.
MANIFEST_APP_URL=http://localhost:8080/
# The client id of your Strava app.
STRAVA_CLIENT_ID=YOUR_CLIENT_ID
# The client secret of your Strava app.
STRAVA_CLIENT_SECRET=YOUR_CLIENT_SECRET
# The refresh of your Strava app. You will need to obtain this token the first time you launch the app.
STRAVA_REFRESH_TOKEN=YOUR_REFRESH_TOKEN
# Strava API has rate limits (https://github.com/robiningelbrecht/statistics-for-strava/wiki),
# to make sure we don't hit the rate limit, we want to cap the number of new activities processed
# per import. Considering there's a 1000 request per day limit and importing one new activity can
# take up to 3 API calls, 250 should be a safe number.
NUMBER_OF_NEW_ACTIVITIES_TO_PROCESS_PER_IMPORT=250
# The schedule to periodically run the import and HTML builds. Leave empty to disable periodic imports.
# The default schedule runs once a day at 04:05. If you do not know what cron expressions are, please leave this unchanged
# Make sure you don't run the imports too much to avoid hitting the Strava API rate limit. Once a day should be enough.
IMPORT_AND_BUILD_SCHEDULE="5 4 * * *"
# Set the timezone used for the schedule
# Valid timezones can found under TZ Identifier column here: https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List
TZ=Etc/GMT

# Allowed options: en_US, fr_FR, nl_BE, de_DE, pt_BR, pt_PT or zh_CN
LOCALE=en_US
# Allowed options: metric or imperial
UNIT_SYSTEM=metric
# Time format to use when rendering the app
# Allowed formats: 24 or 12 (includes AM and PM)
TIME_FORMAT=24
# Date format to use when rendering the app
# Allowed formats: DAY-MONTH-YEAR or MONTH-DAY-YEAR
DATE_FORMAT=DAY-MONTH-YEAR
# Sport types to import. Leave empty to import all sport types
# With this list you can also decide the order the sport types will be rendered in.
# A full list of allowed options is available on https://github.com/robiningelbrecht/statistics-for-strava/wiki/Supported-sport-types/
SPORT_TYPES_TO_IMPORT='[]'
# Activity visibilities to import. Leave empty to import all visibilities
# This list can be combined with SPORT_TYPES_TO_IMPORT.
# Allowed values: ACTIVITY_VISIBILITIES_TO_IMPORT='["everyone", "followers_only", "only_me"]', 
ACTIVITY_VISIBILITIES_TO_IMPORT='[]'
# Optional, the date (YYYY-MM-DD) from which you want to start importing activities. 
# Any activity recorded before this date, will not be imported.
# This can be used if you want to skip the import of older activities. Leave empty to disable.
SKIP_ACTIVITIES_RECORDED_BEFORE=''
# An array of activity ids to skip during import. 
# This allows you to skip specific activities during import.
# ACTIVITIES_TO_SKIP_DURING_IMPORT='["123456789", "987654321"]'
ACTIVITIES_TO_SKIP_DURING_IMPORT='[]'
# Your birthday. Needed to calculate heart rate zones.
ATHLETE_BIRTHDAY=YYYY-MM-DD
# History of weight (in kg or pounds, depending on UNIT_SYSTEM). Needed to calculate relative w/kg.
# Check https://github.com/robiningelbrecht/statistics-for-strava/wiki for more info.
ATHLETE_WEIGHT_HISTORY='{
    "YYYY-MM-DD": 100,
    "YYYY-MM-DD": 200
}'
# The formula used to calculate your max heart rate. The default is Fox (220 - age).
# Allowed values: arena, astrand, fox, gellish, nes, tanaka (https://pmc.ncbi.nlm.nih.gov/articles/PMC7523886/table/t2-ijes-13-7-1242/)
# Or you can set a fixed number for any given date range.  
MAX_HEART_RATE_FORMULA='fox'
# MAX_HEART_RATE_FORMULA='{
#    "2020-01-01": 198,
#    "2025-01-10": 193
# }'
# Optional, history of FTP. Needed to calculate activity stress level.
# Check https://github.com/robiningelbrecht/statistics-for-strava/wiki for more info. Example:
# FTP_HISTORY='{
#    "2024-10-03": 198,
#    "2025-01-10": 220
#}'
FTP_HISTORY='[]'
# Optional, a link to your profile picture. Will be used to display in the nav bar and link to your Strava profile.
# Leave empty to disable this feature.
PROFILE_PICTURE_URL=''
# Optional, your Zwift level (1 - 100). Will be used to render your Zwift badge. Leave empty to disable this feature
ZWIFT_LEVEL=
# Optional, your Zwift racing score (0 - 1000). Will be used to add to your Zwift badge if ZWIFT_LEVEL is filled out.
ZWIFT_RACING_SCORE=
# Optional, full URL with ntfy topic included. This topic will be used to notify you when a new HTML build has run.
# Leave empty to disable notifications.
NTFY_URL=''

# The UID and GID to create/own files managed by statistics-for-strava
# May only be necessary on Linux hosts, see File Permissions in Wiki
#PUID=
#PGID=
```

### Obtaining a Strava refresh token

The first time you launch the app, you will need to obtain a `Strava refresh token`.
The app needs this token to be able to access your data and import it into your local database.

Navigate to http://localhost:8080/. 
You should see this page—just follow the steps to complete the setup.

![Strava Authorization](public/assets/images/readme/strava-oauth.png)

### Importing challenges and trophies

> [!IMPORTANT]
> Only visible challenges on your public profile can be imported. Please make sure that your profile is public,
> otherwise the app won't be able to import them

#### Importing complete history

Strava does not allow to fetch a complete history of your completed challenges and trophies.
There's a little workaround if you'd still like to import these:
* Navigate to https://www.strava.com/athletes/[YOUR_ATHLETE_ID]/trophy-case
* Open the page's source code and copy everything
  ![Trophy case source code](public/assets/images/readme/trophy-case-source-code.png)
* Make sure you save the source code to the file `./storage/files/strava-challenge-history.html`
* On the next import, all your challenges will be imported

> [!WARNING]
> Make sure before you save the source code, your Strava account is set to be translated in English.
> The app can only handle this export in English for now.

## ⚡️Import and build statistics

```bash
docker compose exec app bin/console app:strava:import-data
docker compose exec app bin/console app:strava:build-files
```

## 🗺️ Locales and translations

Currently, the app is translated to:

* 🇬🇧 English
* 🇫🇷 French (thanks to [@llaumgui](https://github.com/llaumgui) and [@Ahmosys](https://github.com/llaumgui))
* 🇩🇪 German (thanks to [@daydreamer77](https://github.com/daydreamer77))
* 🇧🇪 Dutch
* 🇵🇹 Portuguese (thanks to [@jcnmsg](https://github.com/jcnmsg) & [@davisenra](https://github.com/davisenra))
* 🇨🇳 Simplified Chinese (thanks to [@c0j0s](https://github.com/c0j0s))

If you want to see a new locale added, please  [open a new issue](https://github.com/robiningelbrecht/statistics-for-strava/issues/new?template=translations-and-localisation.md). 
Only do this if you are willing to help on the actual translation 🙃.

## 💡 Feature request?

For any feedback, help or feature requests, please [open a new issue](https://github.com/robiningelbrecht/statistics-for-strava/issues/new/choose). 
Before you do, please read [the wiki](https://github.com/robiningelbrecht/statistics-for-strava/wiki). The question you have might be answered over there.

## 🛠️ Local setup

Run the following commands to setup the project on your local machine

```bash
> git clone git@github.com:your-name/your-fork.git
> make composer arg="install"
> make up
```

Everytime you make changes to the app, you need to build the html files again

```bash
> make console arg="app:strava:build-files"
```

