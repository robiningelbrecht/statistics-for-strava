# Advanced scheduling

If you want more control over cron then `IMPORT_AND_BUILD_SCHEDULE` provides then you can take over the crontab file by mounting it into a volume.

```yaml
services:
 #...
   volumes:
     #...
     -  /host/path/to/cron/folder:/config/crontabs
```

After mounting and starting the container once, the `abc` crontab will be created in the host dir.

To disable the container from modifying this file automatically remove

```
printf "AUTO CRON"
```

## Verify schedule
To check if your schedule was applied in the container crontab, you can run

```bash
docker compose exec app crontab -u abc -l
```

After making changes to `abc` you will need to restart the container for the changes to take effect. You may want to also [set correct file permissions](/robiningelbrecht/statistics-for-strava/wiki/File-Permissions) for the crontab file.