# Updates

When a new version of the app is released, you need to pull the latest Docker image:

```bash
> docker compose pull
```

After that, run the import and build commands again to apply the changes:


```bash
> docker compose exec app bin/console app:strava:import-data
> docker compose exec app bin/console app:strava:build-files
```