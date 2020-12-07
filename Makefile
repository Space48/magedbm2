.PHONY: build clean test lint

build: magedbm2.phar

clean:
	rm -rf vendor box.phar composer.phar magedbm2.phar

test: install
	php -derror_reporting=E_ERROR ./vendor/bin/phpunit

install: composer.phar composer.lock
	./composer.phar install

lint: install
	./vendor/bin/phpcs --standard=./phpcs.xml src/ || exit 0
	./vendor/bin/phpmd src/ text ./phpmd.xml || exit 0
	./vendor/bin/phpcbf --standard=./phpcs.xml src/ || exit 0

magedbm2.phar: * box.phar composer.phar
	./composer.phar install --no-dev
	php -d phar.readonly=0 ./box.phar build

composer.phar:
	curl -LSs https://getcomposer.org/installer | php

box.phar:
	curl -LSs https://box-project.github.io/box2/installer.php | php
