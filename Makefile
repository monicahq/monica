GIT_TAG := $(shell git describe --abbrev=0 --tags)
GIT_COMMIT := $(shell git log --format="%h" -n 1)
BUILD := $(GIT_TAG)
ifeq ($(TRAVIS_TAG),)
# If we are not on travis or it's not a TAG build, we add "-dev" to the name
BUILD := $(BUILD)$(shell if ! $$(git describe --abbrev=0 --tags --exact-match 2>/dev/null >/dev/null); then echo "-dev"; fi)
ifneq ($(GIT_TAG),$(BUILD))
ifneq ($(GIT_COMMIT),)
BUILD := $(BUILD)-$(GIT_COMMIT)
endif
endif
endif

DESTDIR := monica-$(BUILD)
ASSETS := monica-assets-$(BUILD)

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
	docker images

docker_tag:
	docker tag monicahq/monicahq monicahq/monicahq:$(GIT_TAG)

docker_push:
	docker push monicahq/monicahq:$(GIT_TAG)
	docker push monicahq/monicahq:latest

docker_push_bintray: .travis.deploy.json
	docker tag monicahq/monicahq monicahq-docker-docker.bintray.io/monicahq/monicahq:$(BUILD)
	docker push monicahq-docker-docker.bintray.io/monicahq/monicahq:$(BUILD)
	BUILD=$(BUILD) scripts/tests/fix-bintray.sh

.PHONY: docker docker_build docker_tag docker_push docker_push_bintray

build: build-dev

build-prod:
	composer install --no-interaction --no-suggest --ignore-platform-reqs --no-dev
	php artisan vue-i18n:generate
	npm install
	npm run production

build-dev:
	composer install --no-interaction --no-suggest --ignore-platform-reqs
	php artisan vue-i18n:generate
	npm install
	npm run dev

prepare: $(DESTDIR) $(ASSETS)
	mkdir -p results

$(DESTDIR):
	mkdir -p $@
	ln -s ../readme.md $@/
	ln -s ../CONTRIBUTING.md $@/
	ln -s ../CHANGELOG $@/
	ln -s ../CONTRIBUTORS $@/
	ln -s ../LICENSE $@/
	ln -s ../.env.example $@/
	ln -s ../composer.json $@/
	ln -s ../composer.lock $@/
	ln -s ../package.json $@/
	ln -s ../package-lock.json $@/
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
	ln -s ../vendor $@/
	mkdir -p $@/storage/app/public
	mkdir -p $@/storage/debugbar
	mkdir -p $@/storage/logs
	mkdir -p $@/storage/framework/views
	mkdir -p $@/storage/framework/cache
	mkdir -p $@/storage/framework/sessions

$(ASSETS):
	mkdir -p $@/public
	ln -s ../../public/mix-manifest.json $@/public/
	ln -s ../../public/js $@/public/
	ln -s ../../public/css $@/public/
	ln -s ../../public/fonts $@/public/

dist: results/$(DESTDIR).tar.bz2 results/$(ASSETS).tar.bz2

COMMIT_MESSAGE := $(shell echo "$$TRAVIS_COMMIT_MESSAGE" | sed -s 's/"/\\\\\\\\\\"/g' | sed -s 's/(/\\(/g' | sed -s 's/)/\\)/g' | sed -s 's%/%\\/%g')

ifeq (,$(DEPLOY_TEMPLATE))
DEPLOY_TEMPLATE := scripts/tests/.travis.deploy.json.in
endif

.travis.deploy.json: $(DEPLOY_TEMPLATE)
	cp $< $@
	sed -si "s/\$$(version)/$(BUILD)/" $@
	sed -si "s/\$$(description)/$(COMMIT_MESSAGE)/" $@
	sed -si "s/\$$(released)/$(shell date -u '+%FT%T.000Z')/" $@
	sed -si "s/\$$(travis_tag)/$(TRAVIS_TAG)/" $@
	sed -si "s/\$$(travis_commit)/$(GIT_COMMIT)/" $@
	sed -si "s/\$$(travis_build_number)/$(TRAVIS_BUILD_NUMBER)/" $@

results/%.tar.xz: % prepare
	tar chfJ $@ --exclude .gitignore --exclude .gitkeep $<

results/%.tar.bz2: % prepare
	tar chfj $@ --exclude .gitignore --exclude .gitkeep $<

results/%.tar.gz: % prepare
	tar chfz $@ --exclude .gitignore --exclude .gitkeep $<

results/%.zip: % prepare
	zip -rq9 $@ $< --exclude "*.gitignore*" "*.gitkeep*"

clean:
	rm -rf $(DESTDIR) $(ASSETS)
	rm -f results/$(DESTDIR).* results/$(ASSETS).* .travis.deploy.json

fullclean: clean
	rm -rf vendor resources/vendor public/fonts/vendor node_modules
	rm -f public/css/* public/js/* public/mix-manifest.json public/storage bootstrap/cache/*

install: .env build-dev
	php artisan key:generate
	php artisan setup:test
	php artisan passport:install

update: .env build-dev
	php artisan migrate

.env:
	cp .env.example .env

.PHONY: dist clean fullclean install update build prepare build-prod build-dev

vagrant_build:
	make -C scripts/vagrant/build package

