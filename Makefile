GIT_TAG := $(shell git describe --abbrev=0 --tags)
VERSION := $(GIT_TAG)$(shell if ! $$(git describe --abbrev=0 --tags --exact-match 2>/dev/null >/dev/null); then echo "-dev"; fi)
BUILD=$(VERSION)
ifneq ($(TRAVIS_BUILD_NUMBER),)
BUILD := $(BUILD)-build$(TRAVIS_BUILD_NUMBER)
endif
DESTDIR := monica-$(BUILD)

default: build

all:
	$(MAKE) fullclean
	$(MAKE) build
	$(MAKE) dist

docker:
	$(MAKE) docker_build
	$(MAKE) docker_tag
	$(MAKE) docker_push

docker_build:
	docker-compose build

docker_tag:
	docker tag monicahq/monicahq monicahq/monicahq:$(GIT_TAG)

docker_push:
	docker push monicahq/monicahq:$(GIT_TAG)
	docker push monicahq/monicahq:latest

docker_push_bintray:
	docker tag monicahq/monicahq monicahq-docker-docker.bintray.io/monicahq/monicahq:$(VERSION)
	docker push monicahq-docker-docker.bintray.io/monicahq/monicahq:$(VERSION)

.PHONY: docker docker_build docker_tag docker_push

build: build-prod

build-prod:
	composer install --no-interaction --prefer-dist --no-suggest --no-dev
	npm install
	npm run production

build-dev:
	composer install --no-interaction --prefer-dist --no-suggest
	npm install
	npm run dev

prepare: $(DESTDIR)
	mkdir -p results

$(DESTDIR):
	mkdir -p $@
	ln -s ../readme.md $@/
	ln -s ../CODE_OF_CONDUCT.md $@/
	ln -s ../CONTRIBUTING.md $@/
	ln -s ../CHANGELOG $@/
	ln -s ../CONTRIBUTORS $@/
	ln -s ../LICENSE $@/
	ln -s ../.env.example $@/
	ln -s ../composer.json $@/
	ln -s ../composer.lock $@/
	ln -s ../package.json $@/
	ln -s ../package-lock.json $@/
	ln -s ../phpunit.xml $@/
	ln -s ../app.json $@/
	ln -s ../nginx_app.conf $@/
	ln -s ../server.php $@/
	ln -s ../webpack.mix.js $@/
	ln -s ../Procfile $@/
	ln -s ../app $@/
	ln -s ../artisan $@/
	ln -s ../bootstrap $@/
	ln -s ../config $@/
	ln -s ../database $@/
	ln -s ../docs $@/
	ln -s ../public $@/
	ln -s ../resources $@/
	ln -s ../routes $@/
	ln -s ../tests $@/
	ln -s ../vendor $@/
	mkdir -p $@/storage/app/public
	mkdir -p $@/storage/debugbar
	mkdir -p $@/storage/logs
	mkdir -p $@/storage/framework/views
	mkdir -p $@/storage/framework/cache
	mkdir -p $@/storage/framework/sessions

dist: results/$(DESTDIR).tar.xz .travis.deploy.json

.travis.deploy.json: .travis.deploy.json.in
	sed -s "s/\$$(version)/$(BUILD)/" $< | \
		sed -s "s/\$$(travis_commit)/$(TRAVIS_COMMIT)/" | \
		sed -s "s/\$$(date)/$(shell date --iso-8601=s)/" > $@

results/$(DESTDIR).tar.xz: prepare
	tar chfJ $@ --exclude .gitignore --exclude .gitkeep $(DESTDIR)

results/$(DESTDIR).tar.bz2: prepare
	tar chfj $@ --exclude .gitignore --exclude .gitkeep $(DESTDIR)

results/$(DESTDIR).tar.gz: prepare
	tar chfz $@ --exclude .gitignore --exclude .gitkeep $(DESTDIR)

results/$(DESTDIR).zip: prepare
	zip -rq9 $@ $(DESTDIR) --exclude "*.gitignore*" "*.gitkeep*"

clean:
	rm -rf $(DESTDIR)
	rm -f results/$(DESTDIR).*
	rm -f .travis.deploy.json

fullclean: clean
	rm -rf vendor resources/vendor public/fonts/vendor node_modules
	rm -f public/css/* public/js/* public/mix-manifest.json public/storage bootstrap/cache/*

install: build-dev
	php artisan key:generate
	php artisan setup:test
	php artisan passport:install

.PHONY: dist clean fullclean install build prepare build-prod build-dev