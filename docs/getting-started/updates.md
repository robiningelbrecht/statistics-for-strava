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

## Check app version

You can verify the version you're running by inspecting the Docker image:

```bash
> docker image inspect [YOUR-IMAGE-ID] --format '{{.Config.Labels}}' | grep org.opencontainers.image.version
```

To find the image ID, run:

```bash
> docker images
```

Then copy the Image ID of the Statistics for Strava container.

