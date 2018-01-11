GIT_TAG=$$(git describe --abbrev=0 --tags)

docker_build:
	docker-compose build

docker_tag:
	docker tag monicahq/monicahq monicahq/monicahq:$(GIT_TAG)

docker_push:
	docker push monicahq/monicahq:$(GIT_TAG)
	docker push monicahq/monicahq:latest
