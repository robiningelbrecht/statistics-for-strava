# :warning: v2.0.0 breaking change

Version `v2.0.0` introduces a breaking change: most configuration values have moved from `.env` to a new `config.yaml` file. This requires manual action on your part.

## Migrate your config from .env

* Update your `docker-compose.yml` and add this extra volume:

```yaml
 services:
   app:
     image: robiningelbrecht/strava-statistics:latest
     volumes:
       - ./config:/var/www/config/app
       # ...
```

* Ensure that a file named `config.yaml` exists inside the `./config` directory.
* Copy and paste the following template and adjust the values to fit your setup:

[include](../configuration/config-yaml-example.md ':include')

* (Optional) You can now safely remove any settings from `.env` that have been moved to `config.yaml`.

## Migrate gear maintenance config

* Copy the existing file from `storage/gear-maintenance/config.yml`
* Paste it into the new Docker volume path you added.
* Rename it to `gear-maintenance.yaml`

<div class="alert success">
You should now be good to go — enjoy the new version! :partying_face:
</div>

