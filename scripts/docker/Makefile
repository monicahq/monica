ROOT_DIR:=$(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))

test_server: waitfordb
	$(ROOT_DIR)/test-server.sh

shell:
	/bin/sh

waitfordb:
	$(ROOT_DIR)/waitfordb.sh
