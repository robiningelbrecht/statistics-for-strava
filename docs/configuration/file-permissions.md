# File permissions

The user running in the strava-statistics container has the UID/GID `65534:100`. 
All the files it touches or creates (under `/var/www`) will be owned with these identifiers.

If you are running strava-statistics on a linux host and mount these directories on the host (as the example `docker-compose.yml` does)
then it's likely your host user will not be able to modify (or potentially read) these files without using `sudo` privileges.

To fix this file permission issue, set the [`PUID` and `PGID`](https://docs.linuxserver.io/general/understanding-puid-and-pgid) variables in your `.env` to equal the host user UID/GID:

```bash
> `id -u` # prints UID
> `id -g` # prints GID
```