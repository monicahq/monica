#!/bin/bash
set -euo pipefail

function api() {
  set +u
  fonction=$1
  action=$2
  content=$3

  if [ -z "${action:-}" ]; then
    curl --silent -H "Authorization: token $GITHUB_TOKEN" https://api.github.com/repos/${TRAVIS_REPO_SLUG}$fonction
  elif [ "$action" = "POST" ]; then
    curl --silent -H "Authorization: token $GITHUB_TOKEN" -d "$content" https://api.github.com/repos/${TRAVIS_REPO_SLUG}$fonction
  else
    curl --silent -H "Authorization: token $GITHUB_TOKEN" -X "$action" https://api.github.com/repos/${TRAVIS_REPO_SLUG}$fonction
  fi
  set -u
}

function uploadimg() {
  file=$1
  curl -H "Authorization: Client-ID $IMGUR_CLIENTID" \
       -H "Expect: " \
	   -F "image=@$file" \
       https://api.imgur.com/3/image 2>/dev/null
}

function deleteimg() {
  hash=$1
  curl -H "Authorization: Client-ID $IMGUR_CLIENTID" \
       -X "DELETE" \
       https://api.imgur.com/3/image/$hash 2>/dev/null
}

if [ "$TRAVIS_PULL_REQUEST" != "false" ] && [ -n "${GITHUB_TOKEN:-}" ]; then

  comments=$(api /issues/$TRAVIS_PULL_REQUEST/comments)

  for ((i=1;i<=4294967295;i++)); do
    comment=$(echo $comments | jq -r ".[$i]")
    if [ "$comment" = "null" ]; then
      break;
    fi

    body=$(echo $comment | jq -r ".body")
    if $(echo $body | grep -q "^\[test\]"); then
      id=$(echo $comment | jq -r ".id")
      hash=$(echo "$body" | grep "\[id:" | sed -s 's/.*\[id\:\(.*\)\]/\1/g' 2>/dev/null)

      echo Delete previous screenshot $id with $hash
      if [ -n "${hash:-}" ]; then
        echo Delete img
        status=$(deleteimg $hash)
      fi
      api /issues/comments/$id DELETE
    fi

  done

  for file in $(ls tests/Browser/screenshots/*.png); do
    echo Upload img and comment for $file
    img=$(uploadimg $file)
    success=$(echo $img | jq -r ".success")
    if [ "$success" = "true" ]; then
      url=$(echo $img | jq -r ".data.link")
      echo $url
      deletehash=$(echo $img | jq -r ".data.deletehash")
      result=$(api /issues/$TRAVIS_PULL_REQUEST/comments POST "{ \"body\": \"[test]\n![capture]($url)\n$file\n[id:$deletehash]\"}")
    fi
  done

fi
