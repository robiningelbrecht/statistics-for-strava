# Import/build fails with syntax error

Multiple users have reported sudden "Syntax" issues while importing or building the HTML files:

* https://github.com/robiningelbrecht/statistics-for-strava/issues/1621
* https://github.com/robiningelbrecht/statistics-for-strava/issues/1623
* https://github.com/robiningelbrecht/statistics-for-strava/issues/1357
* https://github.com/robiningelbrecht/statistics-for-strava/issues/1288
* https://github.com/robiningelbrecht/statistics-for-strava/issues/1180

```bash
```

These issues all have the same root cause, somewhere some data got corrupted. To this day we don't know why or how this happens.

We have created a CLI tool to "fix" the corrupted data, by detecting activities that cause these problems and deleting them.
On the next import run they will be re-imported from Strava. Just run:

```bash
> docker compose exec app bin/console app:data:fix-corrupted-activities
```

This CLI command will guide you through the process of deleting these activities.