ifeq ($(CIRCLECI),true)
  ifneq ($(CIRCLE_PULL_REQUEST),)
    CIRCLE_PR_NUMBER ?= $(shell echo $${CIRCLE_PULL_REQUEST##*/})
  endif
  REPO := $(CIRCLE_PROJECT_USERNAME)/$(CIRCLE_PROJECT_REPONAME)
  BRANCH := $(CIRCLE_BRANCH)
  PR_NUMBER=$(if $(CIRCLE_PR_NUMBER),$(CIRCLE_PR_NUMBER),false)
  BUILD_NUM := $(CIRCLE_BUILD_NUM)
  SHA1 := $(CIRCLE_SHA1)
  TAG := $(CIRCLE_TAG)
  COMMIT_MESSAGE := $(shell git log --format="%s" -n 1)
else
  REPO := $(TRAVIS_REPO_SLUG)
  BRANCH := $(if $(TRAVIS_PULL_REQUEST_BRANCH),$(TRAVIS_PULL_REQUEST_BRANCH),$(TRAVIS_BRANCH))
  PR_NUMBER := $(TRAVIS_PULL_REQUEST)
  BUILD_NUM := $(TRAVIS_BUILD_NUMBER)
  SHA1 := $(if $(TRAVIS_PULL_REQUEST_SHA),$(TRAVIS_PULL_REQUEST_SHA),$(TRAVIS_COMMIT))
  TAG := $(TRAVIS_TAG)
  COMMIT_MESSAGE := $(TRAVIS_COMMIT_MESSAGE)
endif

GIT_TAG := $(shell git describe --abbrev=0 --tags)
GIT_COMMIT := $(shell git log --format="%h" -n 1)
BUILD := $(GIT_TAG)
ifeq ($(TAG),)
  ifeq ($(BRANCH),)
    # If we are not on travis or it's not a TAG build, we add "-dev" to the name
    BUILD := $(GIT_COMMIT)$(shell if ! $$(git describe --abbrev=0 --tags --exact-match 2>/dev/null >/dev/null); then echo "-dev"; fi)
  else
    BUILD := $(BRANCH)
  endif
endif

DESTDIR := monica-$(BUILD)
ASSETS := monica-assets-$(BUILD)

test:
	echo $(BUILD)

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

DOCKER_SQUASH := $(shell which docker-squash)
ifeq ($(TAG),)
  DOCKER_SQUASH := ~/.local/bin/docker-squash
endif

docker_squash:
	docker-squash -t monicahq/monicahq:latest monicahq/monicahq:latest
	docker images

docker_tag:
	docker tag monicahq/monicahq monicahq/monicahq:$(BUILD)

docker_push: docker_tag
	docker push monicahq/monicahq:$(BUILD)
	docker push monicahq/monicahq:latest

docker_push_bintray: .travis.deploy.json
	docker tag monicahq/monicahq monicahq-docker-docker.bintray.io/monicahq/monicahq:$(BUILD)
	docker push monicahq-docker-docker.bintray.io/monicahq/monicahq:$(BUILD)
	BUILD=$(BUILD) scripts/tests/fix-bintray.sh

.PHONY: docker docker_build docker_tag docker_push docker_push_bintray

build: build-dev

build-prod:
	composer install --no-interaction --no-suggest --ignore-platform-reqs --no-dev
	php artisan lang:generate
	yarn install
	yarn run production

build-dev:
	composer install --no-interaction --no-suggest --ignore-platform-reqs
	php artisan lang:generate
	yarn install
	yarn run dev

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
	ln -s ../yarn.lock $@/
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

assets: results/$(ASSETS).tar.bz2

DESCRIPTION := $(shell echo "$(COMMIT_MESSAGE)" | sed -s 's/"/\\\\\\\\\\"/g' | sed -s 's/(/\\(/g' | sed -s 's/)/\\)/g' | sed -s 's%/%\\/%g')

ifeq (,$(DEPLOY_TEMPLATE))
DEPLOY_TEMPLATE := scripts/tests/.travis.deploy.json.in
endif

.travis.deploy.json: $(DEPLOY_TEMPLATE)
	cp $< $@
	sed -si "s/\$$(version)/$(BUILD)/" $@
	sed -si "s/\$$(description)/$(DESCRIPTION)/" $@
	sed -si "s/\$$(released)/$(shell date -u '+%FT%T.000Z')/" $@
	sed -si "s/\$$(travis_tag)/$(TAG)/" $@
	sed -si "s/\$$(travis_commit)/$(GIT_COMMIT)/" $@
	sed -si "s/\$$(travis_build_number)/$(BUILD_NUM)/" $@

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

push_bintray_assets: results/$(ASSETS).tar.bz2 .travis.deploy.json
	INPUT=results/$(ASSETS).tar.bz2 FILE=$(ASSETS).tar.bz2 scripts/tests/bintray-upload.sh

push_bintray_dist: results/$(DESTDIR).tar.bz2 results/$(ASSETS).tar.bz2 .travis.deploy.json
	INPUT=results/$(DESTDIR).tar.bz2 FILE=$(DESTDIR).tar.bz2 scripts/tests/bintray-upload.sh
	INPUT=results/$(ASSETS).tar.bz2 FILE=$(ASSETS).tar.bz2 scripts/tests/bintray-upload.sh
