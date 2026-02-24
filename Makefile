.PHONY: build clean test lint
COMPOSER_EXECUTABLE := $(shell command -v composer 2>/dev/null || echo ./composer.phar)
IMAGE_NAME := magedbm-build-php81
DOCKER_EXECUTABLE := docker run -it --rm -v $(shell pwd):/app $(IMAGE_NAME) bash
# =========================
# PHAR build target commands
# =========================
build:
	@if command -v docker >/dev/null 2>&1; then \
		echo "Docker found. Running build inside Docker..."; \
		$(MAKE) docker-build-phar; \
	else \
		echo "Docker not found. Running build locally..."; \
		$(MAKE) build-phar; \
	fi; \
	echo "⚠️ ⚠️ Update manifest.json with provided signature for new version"

docker-build-phar: docker-build
	@$(DOCKER_EXECUTABLE) -c "\
		make build-phar; \
	"

build-phar: $(shell find src -type f) composer.json composer.phar
	$(COMPOSER_EXECUTABLE) install --no-dev
	php -d phar.readonly=0 vendor/bin/box compile
	make verify

clean:
	rm -rf vendor composer.phar magedbm2.phar

test: install
	php -derror_reporting=E_ERROR ./vendor/bin/phpunit

install: composer.phar composer.lock
	$(COMPOSER_EXECUTABLE) install

lint: install
	./vendor/bin/phpcs --standard=./phpcs.xml src/ || exit 0
	./vendor/bin/phpmd src/ text ./phpmd.xml || exit 0
	./vendor/bin/phpcbf --standard=./phpcs.xml src/ || exit 0

composer.phar:
	@if ! command -v composer >/dev/null 2>&1; then \
		echo "Downloading local composer.phar..."; \
		curl -LSs https://getcomposer.org/installer | php; \
		chmod +x composer.phar; \
	fi

verify:
	vendor/bin/box verify magedbm2.phar

## Docker commands
docker-build:
	@if ! docker image inspect magedbm-build-php81 > /dev/null 2>&1; then \
		docker build -t magedbm-build-php81 . ; \
	fi
docker-ssh: docker-build
	$(DOCKER_EXECUTABLE)
ssh: docker-ssh
