ifeq ($(CIRCLECI),true)
  ifneq ($(CIRCLE_PULL_REQUEST),)
    CIRCLE_PR_NUMBER ?= $(shell echo ${CIRCLE_PULL_REQUEST##*/})
  endif
  REPO := $(CIRCLE_PROJECT_USERNAME)/$(CIRCLE_PROJECT_REPONAME)
  BRANCH := $(CIRCLE_BRANCH)
  PR_NUMBER=$(if $(CIRCLE_PR_NUMBER),$(CIRCLE_PR_NUMBER),false)
  BUILD_NUMBER := $(CIRCLE_BUILD_NUM)
  GIT_COMMIT := $(CIRCLE_SHA1)
  GIT_TAG := $(CIRCLE_TAG)
else ifeq ($(TRAVIS),true)
  REPO := $(TRAVIS_REPO_SLUG)
  BRANCH := $(if $(TRAVIS_PULL_REQUEST_BRANCH),$(TRAVIS_PULL_REQUEST_BRANCH),$(TRAVIS_BRANCH))
  PR_NUMBER := $(TRAVIS_PULL_REQUEST)
  BUILD_NUMBER := $(TRAVIS_BUILD_NUMBER)
  GIT_COMMIT := $(if $(TRAVIS_PULL_REQUEST_SHA),$(TRAVIS_PULL_REQUEST_SHA),$(TRAVIS_COMMIT))
  GIT_TAG := $(TRAVIS_TAG)
  COMMIT_MESSAGE := $(TRAVIS_COMMIT_MESSAGE)
else
  REPO := $(subst https://github.com/,,$(CHANGE_URL))
  ifneq ($(CHANGE_ID),)
    REPO := $(subst /pull/$(CHANGE_ID),,$(REPO))
  endif
  PR_NUMBER := $(CHANGE_ID)
  BRANCH := $(BRANCH_NAME)
endif
$(info REPO=$(REPO))
$(info PR_NUMBER=$(PR_NUMBER))
$(info BRANCH=$(BRANCH))
$(info BUILD_NUMBER=$(BUILD_NUMBER))

ifeq ($(GIT_COMMIT),)
  GIT_COMMIT := $(shell git log --format="%h" -n 1)
else
  GIT_COMMIT := $(shell git rev-parse --short=8 ${GIT_COMMIT})
endif
$(info GIT_COMMIT=$(GIT_COMMIT))
ifeq ($(GIT_TAG),)
  GIT_TAG := $(shell git describe --abbrev=0 --tags --exact-match ${GIT_COMMIT} 2>/dev/null)
endif
$(info GIT_TAG=$(GIT_TAG))
ifeq ($(COMMIT_MESSAGE),)
  COMMIT_MESSAGE := $(shell git log --format="%s" -n 1 ${GIT_COMMIT})
endif
$(info COMMIT_MESSAGE=$(COMMIT_MESSAGE))

BUILD := $(GIT_TAG)
ifeq ($(BUILD),)
  ifeq ($(BRANCH),)
    # If we are not on CI or it's not a TAG build, we add "-dev" to the name
    BUILD := $(GIT_COMMIT)$(shell if ! $$(git describe --abbrev=0 --tags --exact-match ${GIT_COMMIT} 2>/dev/null >/dev/null); then echo "-dev"; fi)
  else
    BUILD := $(BRANCH)
  endif
endif

DESTDIR := monica-$(BUILD)
ASSETS := monica-assets-$(BUILD)
DOCKER_IMAGE := monicahq/monicahq

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
	docker build \
		--build-arg BUILD_DATE=$(shell date -u +"%Y-%m-%dT%H:%M:%SZ") \
		--build-arg VCS_REF=$(GIT_COMMIT) \
		--build-arg VERSION=$(BUILD) \
		-t $(DOCKER_IMAGE) .
	docker images

DOCKER_SQUASH := $(shell which docker-squash)
ifeq ($(DOCKER_SQUASH),)
  DOCKER_SQUASH := ~/.local/bin/docker-squash
endif

docker_squash:
	$(DOCKER_SQUASH) -f $(shell docker image ls -q `head -n 1 Dockerfile | cut -d ' ' -f 2`) -t $(DOCKER_IMAGE):latest $(DOCKER_IMAGE):latest
	docker images

docker_tag:
	docker tag $(DOCKER_IMAGE) $(DOCKER_IMAGE):$(BUILD)

docker_push: docker_tag
	docker push $(DOCKER_IMAGE):$(BUILD)
	docker push $(DOCKER_IMAGE):latest

docker_push_bintray: .deploy.json
	docker tag $(DOCKER_IMAGE) monicahq-docker-docker.bintray.io/$(DOCKER_IMAGE):$(BUILD)
	docker push monicahq-docker-docker.bintray.io/$(DOCKER_IMAGE):$(BUILD)
	BUILD=$(BUILD) scripts/tests/fix-bintray.sh

.PHONY: docker docker_build docker_tag docker_push docker_push_bintray

build:
	composer install --no-interaction --no-suggest --ignore-platform-reqs
	php artisan lang:generate
	yarn install
	yarn lint --fix
	yarn run production

build-prod:
	composer install --no-interaction --no-suggest --ignore-platform-reqs --no-dev
	php artisan lang:generate
	yarn install
	yarn lint --fix
	yarn run production

build-dev:
	composer install --no-interaction --no-suggest --ignore-platform-reqs
	php artisan lang:generate
	yarn install
	yarn lint --fix
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
DEPLOY_TEMPLATE := scripts/tests/.deploy.json.in
endif

.deploy.json: $(DEPLOY_TEMPLATE)
	cp $< $@
	sed -si "s/\$$(version)/$(BUILD)/" $@
	sed -si "s/\$$(description)/$(DESCRIPTION)/" $@
	sed -si "s/\$$(released)/$(shell date -u '+%FT%T.000Z')/" $@
	sed -si "s/\$$(vcs_tag)/$(GIT_TAG)/" $@
	sed -si "s/\$$(vcs_commit)/$(GIT_COMMIT)/" $@
	sed -si "s/\$$(build_number)/$(BUILD_NUMBER)/" $@

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
	rm -f results/$(DESTDIR).* results/$(ASSETS).* .deploy.json

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

push_bintray_assets: results/$(ASSETS).tar.bz2 .deploy.json
	INPUT=results/$(ASSETS).tar.bz2 FILE=$(ASSETS).tar.bz2 scripts/tests/bintray-upload.sh

push_bintray_dist: results/$(DESTDIR).tar.bz2 results/$(ASSETS).tar.bz2 .deploy.json
	INPUT=results/$(DESTDIR).tar.bz2 FILE=$(DESTDIR).tar.bz2 scripts/tests/bintray-upload.sh
	INPUT=results/$(ASSETS).tar.bz2 FILE=$(ASSETS).tar.bz2 scripts/tests/bintray-upload.sh
