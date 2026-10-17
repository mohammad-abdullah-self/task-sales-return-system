.PHONY: install clean-install dev pint pint-blade optimize chown ide fresh

install:
	composer install
	bun install

clean-install:
	rm -rf node_modules
	rm -rf vendor
	composer install
	bun install
	bun run build

dev:
	composer run dev

build:
	bun run build

pint:
	./vendor/bin/pint

pint-blade:
	./vendor/bin/pint --blade

optimize:
	sail artisan optimize:clear

dump:
	sail composer dump-autoload

fresh:
	sail artisan migrate:fresh

seed:
	sail artisan tenants:seed

migrate:
	sail artisan migrate
