compose=docker compose

dc:
	@${compose} -f docker-compose.yml $(cmd)

dcr:
	@make dc cmd="run --rm -e PHP_IDE_CONFIG=serverName=SFS php-cli $(cmd)"

stop:
	@make dc cmd="stop"

up:
	@make dc cmd="up -d"

build-containers:
	@make dc cmd="up -d --build"
	@make dc cmd="--profile on-demand up -d --build"

down:
	@make dc cmd="down"

console:
	@make dcr cmd="bin/console $(arg)"

console-blackfire:
	@make dcr cmd="blackfire run bin/console $(arg)"

console-profiler:
	# We need to use the app container here, otherwise the profiler can't access the files through web requests.
	docker compose exec app bin/console --profile -v $(arg)

composer:
	@make dcr cmd="composer $(arg)"

download-database:
	scp $(user)@$(server):/home/docker/stacks/strava-statistics/storage/database/strava.db ./storage/database/strava.db

# Database migration helpers.
migrate-diff:
	@make console arg="doctrine:migrations:diff"

migrate-run:
	@make console arg="doctrine:migrations:migrate"

# Translation helpers.
translation-extract:
	@make console arg="app:translations:extract"

translation-debug:
	@make console arg="debug:translation en_US"

# Code quality tools.
phpunit:
	@make dcr cmd="vendor/bin/phpunit --order-by=random $(arg)"

phpunit-with-coverage-report:
	@make phpunit arg="--coverage-clover=clover.xml -d --min-coverage=min-coverage-rules.php -d --clean-up-clover-xml"

phpunit-html-coverage:
	@make phpunit arg="--coverage-html var/coverage"

phpstan:
	@make dcr cmd="vendor/bin/phpstan --memory-limit=1G $(arg)"

csfix:
	@make dcr cmd="vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php"
	@make dcr cmd="caddy fmt --overwrite --config=/var/www/docker/app/config/Caddyfile"

delete-snapshots:
	find . -name __snapshots__ -type d -prune -exec rm -rf {} \;

rector:
	@make dcr cmd="vendor/bin/rector"

# Helpers to build the app.
app-import-data:
	docker compose exec app bin/console app:strava:import-data

app-build-files:
	docker compose exec app bin/console app:strava:build-files

app-build-assets:
	@make dcr cmd="npx @tailwindcss/cli -i public/css/tailwind.css -o public/css/dist/tailwind.min.css --minify"
	@make dcr cmd="npx @tailwindcss/cli -i public/css/tailwind.css -o public/css/tailwind.output.css"
	@make dcr cmd="node_modules/.bin/webpack --config webpack.config.js"

app-build-all:
	@make build-containers
	@make app-build-files
	@make app-build-assets
	@make build-containers

app-run-docs:
	docsify serve docs

# Ollama models
ollama-run-llama32:
	docker compose exec ollama ollama pull llama3.2

# Helpers for forks and PRs
fork-fetch-remote:
	git remote add $(remote-name) $(fork-url)
	git fetch $(remote-name) $(fork-branch-name)
	git checkout -b $(remote-name)  $(remote-name)/$(fork-branch-name)

fork-remove:
	git remote remove $(remote-name)