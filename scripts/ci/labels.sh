#!/bin/sh
set -ex

IFS='
'
variable=$1
labels=$2

{
    echo "$variable<<EOF"
    for label in $labels; do
        echo type=image,name=target,annotation-index.$label
    done
    echo 'EOF'
} >> "$GITHUB_OUTPUT"
