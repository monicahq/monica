#!/bin/bash

sudo apache2ctl restart

ROOT=$(cd -P -- "$(dirname -- "$0")/.." && /bin/pwd -P)
nohup meilisearch --master-key=devcontainer-master-key --db-path="$ROOT/storage/meilisearch" --no-analytics > "$ROOT/storage/logs/meilisearch.log" 2>&1 &
