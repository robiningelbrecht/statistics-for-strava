# FAQ

## Why does it take so long to import my data?

Running the import for the first time can take a while, depending on how many activities you have on Strava. 
Strava's API has a `rate limit` of `100 request per 15 minutes` and a `1000 requests per day`. 
We have to make sure this limit is not exceeded. See https://developers.strava.com/docs/rate-limits/. 
The app makes sure there is enough time between each request to not hit the 15-minute limit.

By default, the app will only import `250 new activities per run` to avoid hitting rate limits. 
You can change this number in the `.env` file. 
If you still hit the daily rate limit, the app will automatically import the remaining activities the next day(s). 

## Why is my crontab job not running?

### Verify crontab is correctly copied

Exec into the container and run
```bash
ls -la /config/crontabs
```

Your output should look like this

```bash
total 12
drwxr-xr-x 2 nobody abc 4096 Jun  3 10:26 .
drwxr-xr-x 1 nobody abc 4096 Jun  3 10:26 ..
-rw-r--r-- 1 nobody abc 1185 Jun  3 10:26 abc
```

Importantly, `abc` should be present and owned by `nobody:abc` with at least `read` permissions.

If you are using portainer and if it is not present or has incorrect permissions/ownership,
then make sure portainer is not override the files with a mount/volume somehow.

### Verify cron is running

Exec into the container and run
```bash
ps aux
```
In this list you should see two processes that look like this:

```bash
USER         PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND
root          36  0.0  0.0    220    72 ?        S    10:26   0:00 s6-supervise svc-cron
...
root         160  0.0  0.0   1628   972 ?        Ss   10:26   0:00 busybox crond -f -S -l 5
```

If it's not running it might be that portainer has overridden the entrypoint or command for the container. 
Verify it is **not** overridden and is using the default/empty entrypoint and command.