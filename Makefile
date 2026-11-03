.DEFAULT_GOAL := help
.PHONY: help install serve test lint cs-fix fixtures check screenshots

CONSOLE = php bin/console

help: ## Show this list of tasks
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  \033[32m%-12s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install the PHP and JavaScript dependencies
	composer install

serve: ## Start the local web server (needs the Symfony CLI)
	symfony serve

test: ## Run the tests (pass options with args="--filter=testName")
	./vendor/bin/phpunit $(args)

lint: ## Check the code style, templates, config, translations and database schema
	./vendor/bin/php-cs-fixer fix --dry-run --diff
	$(CONSOLE) lint:twig templates
	$(CONSOLE) lint:yaml config
	$(CONSOLE) lint:container
	$(CONSOLE) lint:translations
	$(CONSOLE) doctrine:schema:validate

cs-fix: ## Fix the PHP code style
	./vendor/bin/php-cs-fixer fix

fixtures: ## Delete the database and create it again with new demo data
	rm -f var/data.db
	$(CONSOLE) doctrine:schema:create --no-interaction
	$(CONSOLE) foundry:load-stories --no-interaction < /dev/null

check: lint test ## Run everything that must pass before a commit

# the port changes when several apps are served at once, so the URL is asked to the Symfony CLI
url ?= $(shell symfony server:status --no-ansi 2>/dev/null | grep -Eo 'https?://[^ ]+' | head -n 1)

screenshots: ## Take the screenshots of the README (needs "symfony serve -d" or url=https://...)
	@test -n "$(url)" || { echo 'The demo is not running. Start it with "symfony serve -d" or pass its URL with url=https://...'; exit 1; }
	npm install --prefix tools/screenshots --no-audit --no-fund
	npx --prefix tools/screenshots playwright install chromium
	SCREENSHOTS_URL=$(url) node tools/screenshots/screenshots.mjs
