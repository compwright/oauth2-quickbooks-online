lint:
	vendor/bin/phpcs --standard=psr2 src/

test: lint
	vendor/bin/phpunit
