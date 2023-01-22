#!/usr/bin/python3
# PYTHON_ARGCOMPLETE_OK
import argparse
import dateutil.parser
import getpass
import os
import requests
from datetime import datetime, timedelta

github_api_accept = 'application/vnd.github.v3+json'


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description='List versions of a GHCR container image you own, and '
        'optionally delete (prune) old, untagged versions.')
    parser.add_argument('--container', default='monica-next',
                        help='name of the container image')
    parser.add_argument('--organization', '-o', default='monicahq',
                        help='name of the organization')
    parser.add_argument('--verbose', '-v', action='store_true',
                        help='print extra debug info')
    parser.add_argument('--prune-age', type=float, metavar='DAYS',
                        default=None,
                        help='delete untagged images older than DAYS days')
    parser.add_argument('--dry-run', '-n', action='store_true',
                        help='do not actually prune images, just list which '
                        'would be pruned')

    # enable bash completion if argcomplete is available
    try:
        import argcomplete
        argcomplete.autocomplete(parser)
    except ImportError:
        pass

    args = parser.parse_args()

    if 'GHCR_TOKEN' in os.environ:
        token = os.environ['GHCR_TOKEN']
    else:
        raise ValueError('missing auth token')

    s = requests.Session()
    s.headers.update({'Authorization': f'token {token}', 'Accept': github_api_accept})

    page = 1
    versions = []
    while True:
        url = f'https://api.github.com/orgs/{args.organization}/packages/container/{args.container}/versions?per_page=100&page={page}'
        if args.verbose:
            print(f'GET {url}')
        r = s.get(url)
        r.raise_for_status()
        if len(r.json()) == 0:
            break
        for v in r.json():
            versions.append(v)
        page += 1

    del_before = datetime.now().astimezone() - timedelta(days=args.prune_age) \
        if args.prune_age is not None else None
    if del_before:
        print(f'Pruning images created before {del_before}')

    for v in versions:
        created = dateutil.parser.isoparse(v['created_at'])
        metadata = v["metadata"]["container"]

        # prune old untagged images if requested
        if del_before is not None and created < del_before \
           and len(metadata['tags']) == 0:
            url = f'https://api.github.com/orgs/{args.organization}/packages/container/{args.container}/versions/{v["id"]}'

            print(f'delete {v["name"]}\t{v["id"]}\t{created}')
            if not args.dry_run:
                if args.verbose:
                    print(f'DELETE {url}')
                r = s.delete(url)
                r.raise_for_status()
                print(f'deleted {v["id"]}')
