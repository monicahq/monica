GIT_TAG := $(shell git describe --abbrev=0 --tags)
VERSION := $(subst v,,$(GIT_TAG))

build: base
	rm -rf .vagrant
	GIT_TAG=$(GIT_TAG) vagrant up monicahq-stable

base:
	vagrant box list | grep -q "ubuntu/bionic64" || vagrant box add ubuntu/bionic64
	vagrant box update --box ubuntu/bionic64

package: build
	rm -f monicahq-stable.box
	vagrant box remove monicahq/monicahq --box-version 0 || true
	vagrant package monicahq-stable --output ./monicahq-stable.box --vagrantfile ../Vagrantfile
	vagrant box add monicahq/monicahq ./monicahq-stable.box

upload:
	mv monicahq-stable.box monicahq-$(GIT_TAG).box
	./vagrant-upload.sh

.PHONY: build base package
