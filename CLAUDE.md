# CLAUDE.md — statistics-for-strava

## Project Purpose

Fork of [strava-statistics](https://github.com/robiningelbrecht/strava-statistics) extended for **hybrid athlete tracking** (cycling, running, and other sports). Self-hosted Strava analytics dashboard — local only, no public deployment.

**Stack**: PHP 8.5 / Symfony 8.0 / Twig / SQLite / FrankenPHP / Docker

## Local Deployment

- **VM**: 101 at `192.168.8.134`
- **Web UI**: `http://192.168.8.134:8081`
- **Credentials**: `.env.local` (not committed); `.env` is the template

Three Docker services:
| Service | Purpose |
|---------|---------|
| `app` | FrankenPHP web server |
| `daemon` | Background cron + webhook processor |
| `php-cli` | On-demand console runner (profile: on-demand) |

## Essential Commands

```bash
# Docker
make up                       # start app + daemon
make down                     # stop all containers
make build-containers         # rebuild images and start

# Development
make console arg="CMD"        # run Symfony console command
make app-build-all            # rebuild CSS/JS/HTML output
make app-build-assets         # rebuild CSS + JS only

# Testing
make phpunit                  # run test suite
make paratest                 # run tests in parallel (faster)
make phpunit-html-coverage    # generate HTML coverage report

# Code Quality (all required to pass CI)
make phpstan                  # static analysis
make csfix                    # auto-format code (PHP-CS-Fixer)
make rector                   # modernize code (Rector)

# Database
make migrate-diff             # generate new migration
make migrate-run              # apply pending migrations
```

Key Symfony console commands:
```bash
app:strava:import-data        # sync activities from Strava API
app:strava:build-app          # generate static build output
app:daemon:run                # background daemon (runs inside daemon container)
```

## Architecture

**Pattern**: CQRS (CommandBus + QueryBus) + Domain-Driven Design

**Source layers** (`src/`):
| Layer | Path | Purpose |
|-------|------|---------|
| Domain | `src/Domain/` | Business logic, models, value objects |
| Application | `src/Application/` | Use cases (RunImport, RunBuild) |
| Infrastructure | `src/Infrastructure/` | Doctrine, HTTP, config, CQRS buses |
| Controllers | `src/Controller/` | HTTP request handlers |
| Console | `src/Console/` | CLI commands |

**Key domains**: Activity, Athlete, Dashboard, Gear, Segment, Challenge, Milestone, Rewind, Ftp, Strava (OAuth/API)

**Database**: SQLite at `storage/database/strava.db`; Doctrine ORM with PHP attributes; migrations in `migrations/`

**Frontend**: Twig templates (`templates/`), Tailwind CSS 4 (`public/css/`), Webpack 5

**Storage volumes** (mounted into containers):
- `config/app/` — runtime app/athlete config YAML
- `build/` — generated static HTML output
- `storage/database/` — SQLite database
- `storage/files/` — uploaded/fetched files
- `storage/gear-maintenance/` — gear maintenance configs

## Testing

- **Framework**: PHPUnit 13 + ParaTest for parallel runs
- **Test DB**: `tests/strava.db` (SQLite, isolated)
- **Structure**: `tests/` mirrors `src/`
- **Snapshots**: Snapshot assertions used widely — update with `--update-snapshots` flag when output intentionally changes
- **CI checks**: PHPStan + Rector + CS-Fixer + full test suite (all must pass on PRs)

## Feature Tracking

**GitHub Issues** — check open issues before starting new features to avoid duplication and align with planned direction.

## Development Notes

- **Upstream**: Periodically merge changes from upstream `strava-statistics` repo
- **Hybrid athlete focus**: Activity type coverage matters — don't assume cycling-only logic; running, walking, swimming, etc. must be considered
- **Local only**: No staging/production environment; all testing done against the VM instance
- **Cadence fix**: Cadence values for runs and walks are doubled at the data layer (see issue #1974)
