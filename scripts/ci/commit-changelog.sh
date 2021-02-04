#!/bin/bash

# GH_TOKEN_RELEASE is mandatory to be able to trigger the Pull Request workflows
if [ -z "$GH_TOKEN_RELEASE" ]; then
    echo "Please provide a token in GH_TOKEN_RELEASE variable" >&2
    exit 1
fi

version=$1

base=master
file=CHANGELOG.md
label=auto-squash
message="chore(changelog): update changelog $version"
newbranch=changelog-$version-$(date +"%Y-%m-%d")

github() {
    method=$1
    apiurl=$2
    shift;shift;
    curl -sSL \
        -X $method \
        -H "Accept: application/vnd.github.v3+json" \
        -H "Authorization: token $GH_TOKEN_RELEASE" \
        https://api.github.com/repos/$GITHUB_REPOSITORY/$apiurl \
        "$@"
}


# Test if branch already exists
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
content=$(base64 -w 0 $file)
github PUT contents/$file \
    -d "{\"message\":\"$message\",\"sha\":$sha,\"branch\":\"$newbranch\",\"content\":\"$content\"}" > /dev/null

# Create a pull request
pr=$(github POST pulls -d "{\"head\":\"$newbranch\",\"base\":\"$base\",\"title\":\"$message\"}")

number=$(echo $pr | jq '.number')
github POST issues/$number/labels -d "{\"labels\":[\"$label\"]}" > /dev/null

url=$(echo $pr | jq '.html_url')
echo "Pull Request created: $url"
