#!/bin/bash

repo=monicahq/monica
base=master
file=CHANGELOG.md
label=auto-squash

newbranch=$(date +"%Y-%m-%d")-update-changelog

github() {
    method=$1
    apiurl=$2
    shift;shift;
    curl -sSL \
        -X $method \
        -H "Accept: application/vnd.github.v3+json" \
        -H "Authorization: token $GITHUB_TOKEN" \
        https://api.github.com/repos/$repo/$apiurl \
        "$@"
}


# Test if branch already exists"
test=$(github GET git/ref/heads/$newbranch -f 2> /dev/null)

if [ $? = 0 ]; then
  # Delete previous branch
  github DELETE git/refs/heads/$newbranch > /dev/null
fi

set -e

echo "Create a new branch: $newbranch"
github POST git/refs -d "{\"ref\":\"refs/heads/$newbranch\",\"sha\":\"$GITHUB_SHA\"}" > /dev/null

# Get current file's sha
sha=$(github GET "contents/$file?ref=$newbranch" | jq '.sha')

echo "Upload new file content"
message="chore(changelog): Update Changelog"
content=$(base64 -w 0 $file)
github PUT contents/$file \
    -d "{\"message\":\"$message\",\"sha\":$sha,\"branch\":\"$newbranch\",\"content\":\"$content\"}" > /dev/null

# Create a pull request
pr=$(github POST pulls -d "{\"head\":\"$newbranch\",\"base\":\"$base\",\"title\":\"$message\"}")

number=$(echo $pr | jq '.number')
github POST issues/$number/labels -d "{\"labels\":[\"$label\"]}" > /dev/null

echo "Pull Request created:"
echo $pr | jq '.html_url'
