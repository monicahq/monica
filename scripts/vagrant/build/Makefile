GIT_TAG := $(shell git describe --abbrev=0 --tags)

build: xenial
	GIT_TAG=$(GIT_TAG) vagrant up monicahq-stable

xenial:
	vagrant box list | grep -q "ubuntu/xenial64" || vagrant box add ubuntu/xenial64
	vagrant box update --box ubuntu/xenial64

package: build
	vagrant package monicahq-stable --output ./monicahq-stable.box
	vagrant box add monicahq/monicahq ./monicahq-stable.box

.PHONY: build xenial package
