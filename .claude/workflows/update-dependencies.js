export const meta = {
  name: 'update-dependencies',
  description: 'Update every dependency of the project (Composer, Bun, Spec Kit, Bun runtime, GitHub Actions, Docker images, Meilisearch) on a dated branch, one signed commit per change',
  whenToUse: 'When the dependencies of this repository need updating. Pass args {"mode": "plan"} to list what would change without changing anything.',
  phases: [
    { title: 'Prepare', detail: 'branch and worktree from an up-to-date main, baseline checks', model: 'sonnet' },
    { title: 'Inventory', detail: 'one reader per ecosystem lists what is outdated', model: 'sonnet' },
    { title: 'Research', detail: 'read the upgrade guide of every major version' },
    { title: 'Apply', detail: 'one change at a time, verified, then committed or reverted' },
    { title: 'Verify', detail: 'full gate on the branch and four independent reviews' },
  ],
}

const planOnly = args === 'plan' || args?.mode === 'plan'

const RULES = `Rules for every step of the update-dependencies workflow:
- Run the quiet form of each command (--quiet, --no-progress, --silent, --compact) and read only the lines you need.
- Bash runs in a sandbox. Signing a commit (gpg), git over SSH, gh, docker and 'specify self upgrade' work only with the sandbox disabled: run those single commands with dangerouslyDisableSandbox. Declare the hosts a command reaches in allowed_domains. The hosts a brief lists are the usual ones, not a limit: declare any other host the work needs, such as a changelog on the project's own site. When Bun, Composer or uv fail with EROFS or "read-only file system" on a cache or temporary directory, point them at $TMPDIR (BUN_TMPDIR, BUN_INSTALL_CACHE_DIR, COMPOSER_CACHE_DIR, UV_CACHE_DIR) and retry.
- Other steps run at the same time and share the same $TMPDIR. Keep your own files (copies of manifests and lock files, downloads, logs, commit messages) in a directory of your own, created once with 'mktemp -d "$TMPDIR/update-dependencies.XXXXXX"': it is "your scratch directory" below. Outside the sandbox $TMPDIR names another directory, so give a command run with the sandbox disabled the absolute path.
- Inside the sandbox, 'git status' in the root checkout lists placeholders the sandbox mounts (.bashrc, .claude/settings.json and others) as untracked. They do not exist and are never staged. A worktree under .claude/worktrees has none.
- A hook called the Fact-Forcing Gate may refuse a first Bash, Write or Edit until you state facts in your text. State what it asks for, then retry the same call.
- Never answer yes to a prompt asking to trust a source, and never pass a flag that skips one, with one exception the user approved: the "Untrusted Source" prompt of 'specify extension add <id> --from <url>', when the repository is one that the specify-upgrade script of composer.json names, the URL is the release asset of an exact version (not a rolling latest one), and the sha256 of that file, downloaded into your scratch directory, equals the digest GitHub publishes for the asset (the digest field in https://api.github.com/repos/<owner>/<repo>/releases/tags/<tag>). Then pipe y into that one command. A source with no published digest, such as a tag archive, is never installed: write the command in notes for the user.
- Never push, never open a pull request, never write the index or the files of the root checkout, and change nothing outside the repository unless your step says so.
- Call something outdated, released or breaking only after reading the registry, the release page or the changelog during this run, never from memory.
- Read GitHub through 'gh api' with the sandbox disabled. Anonymous calls to api.github.com run out of their hourly quota within one run of this workflow, and release notes then go unread.
- The specs/*/.spec-context.json files belong to the repository, and Spec Kit rewrites them on its own (it adds a telemetryInstanceId, among others). Their modifications never make a worktree dirty for this workflow and never fail a step. Whenever 'git status --porcelain' lists them, stage those files alone and commit them with 'git commit --quiet -S -m "chore(docs): spec context"' with the sandbox disabled, then go on. Never stage them in a dependency commit, and never discard them.`

const CHECKS = `How the checks run in this repository, from the worktree:
- PHP tests: 'php artisan view:clear --quiet', then 'php artisan test --parallel --compact' with a Bash timeout of 600000 ms (the suite takes about two minutes). Failures en masse reading "touch(): Utime failed" come from compiled views owned by www-data: clear them and run again. In an agent session laravel/pao replaces Paratest's runner; when the suite crashes before any test runs, run it again with PAO_DISABLE=1 (the path people and CI take) and report both results, since a crash only agents see is still a regression.
- PHPStan: 'vendor/bin/phpstan analyse --no-progress'.
- Pint: 'vendor/bin/pint --dirty --format agent' leaves nothing to fix.
- Translations: 'php artisan monica:localize --check'.
- Assets: 'bun run build'.
- Composer: 'composer validate --no-check-publish' and 'composer audit --locked --abandoned=report --no-interaction' (two abandoned dragon-code packages make a plain 'composer audit' fail on main already).
A check passes when it is no worse than the baseline: no test failing that passed at baseline, no PHPStan error beyond the baseline count.`

const CHANGE = {
  type: 'object',
  properties: {
    name: { type: 'string' },
    from: { type: 'string' },
    to: { type: 'string' },
  },
  required: ['name', 'from', 'to'],
}

const HELD_BACK = {
  type: 'object',
  properties: {
    name: { type: 'string' },
    current: { type: 'string' },
    available: { type: 'string' },
    reason: { type: 'string' },
  },
  required: ['name', 'current', 'available', 'reason'],
}

const CHECK = {
  type: 'object',
  properties: {
    name: { type: 'string' },
    result: { type: 'string', enum: ['pass', 'fail', 'not-run'] },
    detail: { type: 'string' },
  },
  required: ['name', 'result', 'detail'],
}

const STRINGS = { type: 'array', items: { type: 'string' } }

const PREPARE = {
  type: 'object',
  properties: {
    ok: { type: 'boolean' },
    reason: { type: 'string' },
    root: { type: 'string' },
    worktree: { type: 'string' },
    branch: { type: 'string' },
    baseSha: { type: 'string' },
    baselineFailingTests: STRINGS,
    baselinePhpstanErrors: { type: 'number' },
    baselineChecks: { type: 'array', items: CHECK },
    notes: STRINGS,
  },
  required: ['ok', 'reason', 'root', 'worktree', 'branch', 'baseSha', 'baselineFailingTests', 'baselinePhpstanErrors', 'baselineChecks', 'notes'],
}

const ITEM = {
  type: 'object',
  properties: {
    id: { type: 'string' },
    kind: { type: 'string', enum: ['routine', 'major'] },
    title: { type: 'string' },
    changes: { type: 'array', items: CHANGE },
    files: STRINGS,
    commitMessage: { type: 'string' },
    howToApply: { type: 'string' },
    howToVerify: { type: 'string' },
    sources: STRINGS,
  },
  required: ['id', 'kind', 'title', 'changes', 'files', 'commitMessage', 'howToApply', 'howToVerify', 'sources'],
}

const INVENTORY = {
  type: 'object',
  properties: {
    items: { type: 'array', items: ITEM },
    heldBack: { type: 'array', items: HELD_BACK },
    notes: STRINGS,
  },
  required: ['items', 'heldBack', 'notes'],
}

const RESEARCH = {
  type: 'object',
  properties: {
    worthAttempting: { type: 'boolean' },
    risk: { type: 'string', enum: ['low', 'medium', 'high'] },
    reason: { type: 'string' },
    breakingChanges: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          change: { type: 'string' },
          locations: STRINGS,
        },
        required: ['change', 'locations'],
      },
    },
    coupledChanges: { type: 'array', items: CHANGE },
    plan: { type: 'string' },
    checks: { type: 'string' },
    sources: STRINGS,
  },
  required: ['worthAttempting', 'risk', 'reason', 'breakingChanges', 'coupledChanges', 'plan', 'checks', 'sources'],
}

const APPLY = {
  type: 'object',
  properties: {
    status: { type: 'string', enum: ['committed', 'reverted', 'failed'] },
    commitSha: { type: 'string' },
    commitMessage: { type: 'string' },
    signed: { type: 'boolean' },
    changes: { type: 'array', items: CHANGE },
    heldBack: { type: 'array', items: HELD_BACK },
    checks: { type: 'array', items: CHECK },
    codeEdits: STRINGS,
    notes: STRINGS,
  },
  required: ['status', 'commitSha', 'commitMessage', 'signed', 'changes', 'heldBack', 'checks', 'codeEdits', 'notes'],
}

const GATE = {
  type: 'object',
  properties: {
    green: { type: 'boolean' },
    checks: { type: 'array', items: CHECK },
    regressions: STRINGS,
  },
  required: ['green', 'checks', 'regressions'],
}

const FINDINGS = {
  type: 'object',
  properties: {
    findings: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          severity: { type: 'string', enum: ['blocker', 'fix', 'note'] },
          file: { type: 'string' },
          summary: { type: 'string' },
          evidence: { type: 'string' },
        },
        required: ['severity', 'file', 'summary', 'evidence'],
      },
    },
  },
  required: ['findings'],
}

const FIX = {
  type: 'object',
  properties: {
    confirmed: STRINGS,
    dismissed: STRINGS,
    commits: STRINGS,
    unresolved: STRINGS,
  },
  required: ['confirmed', 'dismissed', 'commits', 'unresolved'],
}

const ECOSYSTEMS = [
  {
    key: 'composer',
    title: 'Composer packages',
    brief: `Files: composer.json, composer.lock. Read 'composer outdated --direct --format=json --no-interaction'. Resolve on copies of composer.json and composer.lock in your scratch directory ('composer update --no-install --no-scripts --no-plugins') to see the whole change before applying it.
'composer update' runs post-update-cmd: it publishes the framework assets, links CLAUDE.md to .claude/CLAUDE.md, runs 'php artisan boost:update' (which rewrites the <laravel-boost-guidelines> block of .claude/CLAUDE.md and the Boost skills listed in boost.json under .claude/skills), then removes the link. Every step ends in '|| true', so a failure is silent: check that the output says "Boost guidelines and skills updated successfully" and that no CLAUDE.md is left at the root, and run 'composer run-script post-update-cmd' again when it does not. Never run 'php artisan boost:update' on its own: without the link it writes a real CLAUDE.md at the root. The files Boost rewrites belong to the routine commit.
When laravel/pint moves, the routine item runs 'vendor/bin/pint --format agent' on the whole tree and its changes ride in the same commit.
A package constrained to an exact version (no caret) is held on purpose, and composer.json cannot say why: read the commit that set it ('git log -S <package> -- composer.json') and keep it held while the reason it gives still stands.
Hosts: repo.packagist.org, packagist.org, github.com, api.github.com, codeload.github.com.`,
    checks: 'the PHP tests, PHPStan, Pint, the translations check, composer validate and audit',
    commit: "routine: 'chore(deps): update composer packages'; a major: 'chore(deps): upgrade <package> to <major>'",
  },
  {
    key: 'bun-runtime',
    title: 'The Bun version the project declares',
    brief: `Files: the packageManager field of package.json and the oven/bun:<version>-debian image the Dockerfile copies Bun from. Both must name the same version. Find the latest stable release ('npm view bun version', https://github.com/oven-sh/bun/releases, the release posts on bun.com/blog). Canary builds do not count.
A newer release in the same major is a routine item touching package.json, the Dockerfile and bun.lock (regenerated with 'bun install'). It runs before the Bun packages, so that bun.lock is written by the version the project declares.
Report the Bun installed on this machine ('bun --version'). This workflow never upgrades the local binary: when it is older than the target, say so in notes.
Hosts: registry.npmjs.org, github.com, api.github.com, bun.com, hub.docker.com.`,
    checks: "'bun install --frozen-lockfile' and 'bun run build'; the oven/bun tag exists on Docker Hub; the Docker build runs in the final gate",
    commit: "'chore(build): upgrade bun to <version>'",
  },
  {
    key: 'bun-packages',
    title: 'JavaScript packages installed with Bun',
    brief: `Files: package.json (devDependencies), bun.lock. Read 'bun outdated' with BUN_TMPDIR=$TMPDIR. Each caret in package.json names the installed version; the routine item keeps it that way ('bun update', without --latest). Simulate on copies of package.json and bun.lock in your scratch directory ('bun update --lockfile-only') to list the whole change.
For a major, find where the package is used: vite.config.js, resources/js, resources/css, and the 'dev' script of composer.json, which runs 'bunx concurrently'. Read its engines field against the Node version the project runs (the node image of the Dockerfile).
Hosts: registry.npmjs.org.`,
    checks: "'bun install --frozen-lockfile', 'bun run build', and for a command line tool the command line the project runs it with",
    commit: "routine: 'chore(deps): update bun packages'; a major: 'chore(deps): upgrade <package> to <major>'",
  },
  {
    key: 'speckit',
    title: 'GitHub Spec Kit',
    brief: `Spec Kit is the global 'specify' CLI (a uv tool), the files its Claude integration generated (.specify/scripts, .specify/templates, .specify/integrations, .claude/skills/speckit-*), and four extensions (.specify/extensions, versions in .specify/extensions/.registry). The 'specify-upgrade' script of composer.json names where each extension comes from. Read it, but never run it as a whole: it reinstalls every extension, some from a rolling URL.
Compare 'specify --version', 'specify self check', .specify/integration.json and the registry with the latest releases: the CLI at github.com/github/spec-kit; companion at github.com/alfredoperez/speckit-companion (releases tagged speckit-ext-v<version>); archive at github.com/stn1slv/spec-kit-archive ('git ls-remote --tags'); bug and assess in the Spec Kit catalog ('specify extension info bug'). 'specify extension info' writes a catalog cache to .specify/extensions/.cache, so during the inventory run it from a copy of .specify in your scratch directory.
An extension whose version starts with 0 moves a major when its minor moves: that is a major item.
The routine item (the CLI and the extensions within their majors) does, in this order:
1. Find the files whose content no longer matches the hash recorded at install time (the files maps of .specify/integrations/*.manifest.json, the manifest_hash of each extension.yml in the registry). These are local edits, and --force rewrites every file without looking at hashes, whatever the header of .specify/.gitignore says. Save each one in your scratch directory together with its base, the content whose hash matched the manifest: find it in 'git log -- <file>', or, when no commit has it, in the source of the CLI that wrote it (.specify/.gitignore comes from SPECIFY_GITIGNORE_CONTENT in specify_cli/shared_infra.py). Read that source before the upgrade replaces it.
2. 'specify self upgrade' with the sandbox disabled (the user approved changing the global CLI under ~/.local), then check 'specify --version'.
3. 'specify integration upgrade --force'. Reinstall an extension only when a newer release exists: 'specify extension add <id> --force' from the catalog, or from a URL under the trust rule. When a newer version is installed from a URL, the specify-upgrade script names that exact URL afterwards (the release asset or the tag it came from), never a rolling latest one, which the trust rule refuses.
4. Merge every saved local edit back with 'git merge-file <file> <base> <saved copy>'. Resolve a conflict by keeping both intents and report what you could not keep.
A new major of the CLI is a major item.
Hosts: github.com, api.github.com, codeload.github.com, objects.githubusercontent.com, release-assets.githubusercontent.com, raw.githubusercontent.com, pypi.org, files.pythonhosted.org.`,
    checks: "'specify --version' names the new version; '.specify/scripts/bash/check-prerequisites.sh --help' runs; every extension of .specify/extensions.yml is still installed and enabled, with its hooks; each local edit is still there; nothing outside .specify, .claude/skills/speckit-* and the composer.json script changed",
    commit: "'chore(deps): upgrade spec kit to <version>'; an extension major: 'chore(deps): upgrade spec kit <extension> to <version>'",
  },
  {
    key: 'github-actions',
    title: 'GitHub Actions',
    brief: `Every 'uses:' line of .github/workflows/*.yml, those in commented-out jobs included, so they still work when somebody uncomments them. The project pins major tags (@v6), so releases inside a major arrive on their own.
List the tags of each action ('git ls-remote --tags https://github.com/<owner>/<repo>') and read the release notes of every newer major for changes to the inputs this repository passes (its with: blocks), to the events it runs on, and to the runner requirements. Compare action.yml between the two tags.
All bumps go in one major item when none of them breaks an input used here; an action that does gets an item of its own.
Hosts: github.com, api.github.com, raw.githubusercontent.com.`,
    checks: "each changed 'uses:' resolves to an existing tag ('git ls-remote'), the YAML still parses, the release notes show no break for the inputs used here; CI cannot run locally, so say so",
    commit: "'chore(ci): update github actions'",
  },
  {
    key: 'docker-images',
    title: 'Container images',
    brief: `Image tags in the Dockerfile (FROM and COPY --from), compose.yaml and .devcontainer/devcontainer.json (image and features). docker-compose.yml is generated by Laravel Sail and stays out. The oven/bun tag belongs to the Bun runtime ecosystem. PHP image tags are held back.
A floating tag (node:24, mariadb:12, composer:2) already follows the releases inside its major. An image moves to a new major only when that major is a long term support line, which the user decided: Node when its lts field is set in https://nodejs.org/dist/index.json, MariaDB when https://downloads.mariadb.org/rest-api/mariadb/ lists it with long term support. For other images and the devcontainer features, a new stable major (Docker Hub https://hub.docker.com/v2/namespaces/library/repositories/<name>/tags, ghcr.io for features).
Read the comment next to each pin: it says why the pin is what it is.
Hosts: hub.docker.com, registry-1.docker.io, auth.docker.io, production.cloudflare.docker.com, ghcr.io, nodejs.org, downloads.mariadb.org.`,
    checks: "'docker build' of the Dockerfile and 'docker compose config --quiet', both with the sandbox disabled; a devcontainer change cannot be built here, so say so",
    commit: "'chore(build): update docker images'",
  },
  {
    key: 'meilisearch',
    title: 'Meilisearch server',
    brief: `The Meilisearch server version is pinned twice, and both must agree: the service image of .github/workflows/tests.yml and the binary .devcontainer/postCreate.sh downloads. docker-compose.yml (Sail) uses a latest tag and stays out.
Find the latest stable release (https://api.github.com/repos/meilisearch/meilisearch/releases/latest), read its notes for behaviour changes that reach this repository (search settings, ranking, embedders), check that the image tag exists on Docker Hub, and check that the installed meilisearch/meilisearch-php client supports it ('composer show meilisearch/meilisearch-php', its compatibility notes). A newer release in the same major is a routine item.
Hosts: api.github.com, github.com, objects.githubusercontent.com, release-assets.githubusercontent.com, hub.docker.com.`,
    checks: 'download the new binary into your scratch directory, start it with a master key on a free port, and run the tests that need a real engine against it (grep tests for MEILISEARCH_HOST to see how they find one)',
    commit: "'chore(ci): upgrade meilisearch to <version>'",
  },
]

const LENSES = [
  {
    key: 'local-edits',
    options: { model: 'sonnet' },
    prompt: `Did a regeneration discard something the project wrote by hand? Spec Kit runs with --force, 'boost:update' rewrites .claude/CLAUDE.md and the Boost skills, 'vendor:publish --force' rewrites published files. For every file such a tool changed, read its history ('git log -p -- <file>'): a removed hunk that a project commit added, rather than an earlier run of the same generator, is a loss. .claude/CLAUDE.md must be unchanged outside its <laravel-boost-guidelines> block. The project's own skills (those neither Boost nor Spec Kit installs) and .specify/memory must be untouched.`,
  },
  {
    key: 'consistency',
    options: { model: 'sonnet' },
    prompt: `Every pin that names the same thing must agree: the Bun version (packageManager in package.json, the oven/bun tag in the Dockerfile), the Meilisearch version (tests.yml, .devcontainer/postCreate.sh), the Node version (the Dockerfile, the commented setup-node step of static.yml), the Spec Kit version (.specify/init-options.json, .specify/integration.json, the manifests), each extension version (the registry, its extension.yml, a pin in the composer.json script), composer.json against composer.lock. A comment next to a pin must still tell the truth.`,
  },
  {
    key: 'commits',
    options: { model: 'haiku', effort: 'low' },
    prompt: `Every commit of the range is signed ('git log --format=%G?' prints G), has the subject 'type(scope): short sentence' with scope deps, build or ci and no attribution line, holds one item, and carries nothing that belongs elsewhere (.env, a CLAUDE.md symbolic link at the root, build output, stray files). The branch is named YYYY-MM-DD-update-dependencies.`,
  },
  {
    key: 'code-edits',
    options: {},
    prompt: `Every edit outside lock files, manifests and generated files was made to fit an upgrade. Is each one correct, minimal, and in line with CLAUDE.md and .claude/rules (code style, testing)? Was any test weakened (skipped, an assertion removed or loosened), any PHPStan ignore or baseline entry added?`,
  },
]

const briefOf = (item) => ECOSYSTEMS.find(ecosystem => ecosystem.key === item.ecosystem).brief

const inventoryPrompt = (ecosystem, location) => `${RULES}

Inventory one ecosystem of dependencies. Read only: change no file, install nothing, upgrade nothing.
Repository: ${location}
Ecosystem: ${ecosystem.title}
${ecosystem.brief}

How to classify what you find:
- routine: everything that moves within the constraints the project already declares (composer.json, package.json ranges), and a patch or minor bump of a pinned version. All the routine changes of this ecosystem form one item.
- major: anything that crosses a major version (or a 0.x minor), or needs a constraint edit. One item per major, together with the packages that must move with it ('composer why-not <package> <version>', peer dependencies).
- heldBack: what you deliberately leave, with the reason: the PHP version wherever it is named (a platform decision this workflow never moves), a pin whose neighbouring comment explains why it stays, a release that is not stable, a package an upstream constraint blocks.
Return no item for something already current.

For every item give: an id in kebab-case starting with "${ecosystem.key}-", the files it touches, the exact commands that apply it, the checks that prove it (${ecosystem.checks}), the conventional commit subject (${ecosystem.commit}), and the URLs you read.`

const researchPrompt = (location, baseRevision, item) => `${RULES}

Research one major upgrade before another agent applies it. Read only: change no file, install nothing, and run nothing that writes into the repository. Other agents apply updates to this repository while you read it, so take composer.json, composer.lock, package.json and bun.lock as they stand at ${baseRevision} ('git show ${baseRevision}:composer.lock'), and run any resolver (composer, bun) on those copies in your scratch directory, never in place.
Repository: ${location}
How the project handles this ecosystem:
${briefOf(item)}

Item:
${JSON.stringify(item, null, 2)}

1. Read the upgrade guide, the changelog and the release notes of every version between the current one and the target. Start from the item's sources, then the project's releases and its UPGRADE file. Keep the URLs.
2. Find every place in this repository a breaking change reaches: code, configuration, tests, scripts, CI, Dockerfile. Search for the APIs, options, attributes, command line flags and defaults that changed, and cite file:line. Write "none found" only after searching.
3. List what has to move with it: other packages' constraints ('composer why-not <package> <version>', peer dependencies).
4. Write the plan the next agent follows: the exact constraint edits and commands, the code edit per file, and the checks that prove it.
Set worthAttempting to false only when the upgrade cannot work yet (a required package has no compatible release, the target is not stable), and say why.`

const preparePrompt = `${RULES}

Prepare the branch and the worktree this update runs in, then record the baseline. This is the one step allowed to touch the root checkout, and only to fast-forward main.

1. The root is 'git rev-parse --show-toplevel' in your current directory. The branch is '<YYYY-MM-DD>-update-dependencies' with today's date ('date +%F').
2. 'git fetch --quiet origin main' (SSH, so the sandbox is disabled). When the root checkout is on main and 'git status --porcelain --untracked-files=no' prints nothing, fast-forward it with 'git merge --ff-only --quiet origin/main'. Otherwise leave it and say why in notes.
3. If the branch or <root>/.claude/worktrees/<branch> already exists, stop and return ok=false with that reason. Never delete, reset or reuse them.
4. Create the branch without writing .git/config: .git/config.lock is a device node here, so 'git checkout -b', 'git switch -c', a tracking 'git branch' and 'push -u' fail halfway. Run 'git update-ref refs/heads/<branch> origin/main', then 'git worktree add --quiet <root>/.claude/worktrees/<branch> <branch>' (.git/info/exclude already ignores that folder).
5. In the worktree, copy .env and storage/oauth-*.key from the root when they exist (git ignores them). Then run 'composer install --no-interaction --no-progress --quiet', 'bun install --frozen-lockfile --silent', 'bun run build' and 'php artisan view:clear --quiet'.
6. Record the baseline before anything changes: the PHP tests, PHPStan, 'php artisan monica:localize --check' and the composer audit. Give the failing test names exactly and the PHPStan error count. Fix nothing: every later step is compared against this.

${CHECKS}`

const applyPrompt = (prepared, item, research, committed) => `${RULES}

Apply one dependency change in the worktree ${prepared.worktree} (branch ${prepared.branch}), verify it, then either commit it or put the worktree back exactly as it was. Work only in that worktree.

How the project handles this ecosystem (this brief and the rules above win over the item where they differ):
${briefOf(item)}

Item, proposed by the inventory:
${JSON.stringify(item, null, 2)}
${research ? `\nResearch made before this step, read only, by another agent. Check it against what you see:\n${JSON.stringify(research, null, 2)}\n` : ''}
Baseline of this branch before any change: failing tests ${JSON.stringify(prepared.baselineFailingTests)}, PHPStan errors ${prepared.baselinePhpstanErrors}.
Already committed on this branch: ${committed.length ? committed.map(result => result.commitMessage).join('; ') : 'nothing yet'}.

1. Commit the specs/*/.spec-context.json files 'git status --porcelain' lists, as the rules say. After that it must print nothing. If it prints anything else, return status failed with what you found, and change nothing.
2. Apply the change.
3. Verify it with the item's checks.
${item.kind === 'major'
  ? '   When a check fails, fix the code the upgrade broke, following CLAUDE.md and .claude/rules, starting from the breaking changes the research lists and the lines the failures point at. Never weaken a test (skip it, delete or loosen an assertion) and never add a PHPStan ignore or baseline entry. If it is still red after that, revert (step 5).'
  : '   When a check fails, find the package responsible (update the packages one at a time, or bisect), keep that one at its current version, record it in heldBack with the failure, and land the rest.'}
4. Commit when green. Read 'git status --porcelain' and stage by path the files the item touches and the files its tools regenerated for it (lock files, generated skills), and nothing else: no .env, no CLAUDE.md symbolic link at the root, no build output. Run 'git commit --quiet -S' with the sandbox disabled. The subject is the item's commit subject (conventional, scope deps, build or ci, no attribution line); the body has one line per package, "name from → to". Check that 'git log -1 --format=%G?' prints G. Then delete any untracked file the item left that does not belong in the repository, so the next step starts clean.
5. Revert when not green: commit the specs/*/.spec-context.json files as the rules say, then 'git reset --quiet --hard HEAD' and 'git clean -fdq' (this worktree holds nothing but your attempt), then 'composer install --no-interaction --no-progress --quiet', 'bun install --frozen-lockfile --silent' and 'bun run build', so that vendor, node_modules and public/build match the lock files again. A global tool this step upgraded (the specify CLI) stays upgraded: say so in notes.

${CHECKS}`

const gatePrompt = (prepared) => `${RULES}

Run the full gate on branch ${prepared.branch} in the worktree ${prepared.worktree}. It holds every commit of this run on top of ${prepared.baseSha}. Fix nothing: report.

Run every check below, and also:
- 'bun install --frozen-lockfile', and 'bun audit' when the installed Bun has that command.
- 'docker build --quiet -t update-dependencies-gate .' with the sandbox disabled, then remove that image. The image installs composer.lock and bun.lock with the pinned Bun, so it proves the lock files and the pins together.
- 'docker compose config --quiet' when 'git diff --name-only ${prepared.baseSha}..HEAD' lists compose.yaml.
- 'git status --porcelain' prints nothing at the end, once the specs/*/.spec-context.json files are committed as the rules say.
Name every regression against the baseline: failing tests ${JSON.stringify(prepared.baselineFailingTests)}, PHPStan errors ${prepared.baselinePhpstanErrors}.

${CHECKS}`

const reviewPrompt = (prepared, lens) => `${RULES}

Review the range ${prepared.baseSha}..HEAD of branch ${prepared.branch} in the worktree ${prepared.worktree} through one lens. Read only.
${lens.prompt}
Give each finding its evidence (file:line, command output) and a severity: blocker (wrong or broken), fix (should change before merge), note (worth knowing). Report nothing you have not checked.`

const fixPrompt = (prepared, gate, findings) => `${RULES}

The gate and the reviews of branch ${prepared.branch} (worktree ${prepared.worktree}) reported the problems below. Other agents wrote them: confirm each one against the files before acting, and dismiss with a reason what does not hold.

Gate:
${JSON.stringify(gate, null, 2)}

Findings:
${JSON.stringify(findings, null, 2)}

Fix what you confirmed, in the worktree only, with new commits. Never amend, rebase or rewrite a commit already made. Each commit is signed ('git commit --quiet -S', sandbox disabled) and its subject is 'type(scope): short sentence' with scope deps, build or ci. When fixing a regression means undoing an item, run 'git revert --no-edit -S <sha>' and say so. Run the checks that cover what you changed.

${CHECKS}`

let prepared = null
let location = 'the root checkout (git rev-parse --show-toplevel in your current directory)'
let baseRevision = 'HEAD'

if (!planOnly) {
  phase('Prepare')
  prepared = await agent(preparePrompt, { label: 'prepare', phase: 'Prepare', schema: PREPARE, model: 'sonnet' })
  if (!prepared?.ok) {
    return { mode: 'apply', stopped: prepared?.reason ?? 'the prepare step did not finish', prepared }
  }
  location = `the worktree ${prepared.worktree} (branch ${prepared.branch})`
  baseRevision = prepared.baseSha
  log(`Branch ${prepared.branch} in ${prepared.worktree}`)
}

phase('Inventory')
const inventories = await parallel(ECOSYSTEMS.map(ecosystem => () =>
  agent(inventoryPrompt(ecosystem, location), { label: `inventory:${ecosystem.key}`, phase: 'Inventory', schema: INVENTORY, model: 'sonnet' })
    .then(result => result && { ecosystem: ecosystem.key, ...result })))

const inventoryMissing = ECOSYSTEMS.filter((ecosystem, index) => !inventories[index]).map(ecosystem => ecosystem.key)
if (inventoryMissing.length) {
  log(`Inventory did not finish for: ${inventoryMissing.join(', ')}`)
}

const inventoried = inventories.filter(Boolean)
const items = inventoried.flatMap(inventory => inventory.items.map(item => ({ ecosystem: inventory.ecosystem, ...item })))
const heldBack = inventoried.flatMap(inventory => inventory.heldBack.map(entry => ({ ecosystem: inventory.ecosystem, ...entry })))
const notes = inventoried.flatMap(inventory => inventory.notes.map(note => `${inventory.ecosystem}: ${note}`))
const routineItems = items.filter(item => item.kind === 'routine')
const majorItems = items.filter(item => item.kind === 'major')
log(`${routineItems.length} routine item(s), ${majorItems.length} major item(s)`)

const researchMajors = () => parallel(majorItems.map(item => () =>
  agent(researchPrompt(location, baseRevision, item), { label: `research:${item.id}`, phase: 'Research', schema: RESEARCH })))

if (planOnly) {
  const research = await researchMajors()
  return {
    mode: 'plan',
    routineItems,
    majorItems: majorItems.map((item, index) => ({ ...item, research: research[index] })),
    heldBack,
    notes,
    inventoryMissing,
  }
}

phase('Apply')
const applied = []
let haltedAfter = null

const applyItem = async (item, research) => {
  const committed = applied.filter(result => result.status === 'committed')
  const result = await agent(applyPrompt(prepared, item, research, committed), { label: `apply:${item.id}`, phase: 'Apply', schema: APPLY })
  const recorded = {
    id: item.id,
    kind: item.kind,
    title: item.title,
    ...(result ?? { status: 'failed', commitSha: '', commitMessage: item.commitMessage, signed: false, changes: item.changes, heldBack: [], checks: [], codeEdits: [], notes: ['the agent did not finish'] }),
  }
  applied.push(recorded)
  log(`${item.id}: ${recorded.status}`)
  if (recorded.status === 'failed') {
    haltedAfter = item.id
  }
}

const routineApplied = (async () => {
  for (const item of routineItems) {
    if (haltedAfter) {
      return
    }
    await applyItem(item, null)
  }
})()

const research = await researchMajors()
await routineApplied

const RISK_ORDER = { low: 0, medium: 1, high: 2 }
const majors = majorItems.map((item, index) => ({ item, research: research[index] }))
const majorsNotAttempted = majors
  .filter(major => !major.research?.worthAttempting)
  .map(major => ({ id: major.item.id, title: major.item.title, reason: major.research?.reason ?? 'the research did not finish' }))
const majorsToAttempt = majors
  .filter(major => major.research?.worthAttempting)
  .sort((first, second) => RISK_ORDER[first.research.risk] - RISK_ORDER[second.research.risk])

for (const major of majorsToAttempt) {
  if (haltedAfter) {
    break
  }
  await applyItem(major.item, major.research)
}

const notRun = [...routineItems, ...majorsToAttempt.map(major => major.item)]
  .filter(item => !applied.some(result => result.id === item.id))
  .map(item => ({ id: item.id, title: item.title, reason: `not run: the run stopped after ${haltedAfter} failed` }))

const summary = {
  mode: 'apply',
  branch: prepared.branch,
  worktree: prepared.worktree,
  baseSha: prepared.baseSha,
  prepareNotes: prepared.notes,
  baseline: { failingTests: prepared.baselineFailingTests, phpstanErrors: prepared.baselinePhpstanErrors, checks: prepared.baselineChecks },
  committed: applied.filter(result => result.status === 'committed'),
  reverted: applied.filter(result => result.status === 'reverted'),
  failed: applied.filter(result => result.status === 'failed'),
  notAttempted: [...majorsNotAttempted, ...notRun],
  heldBack: [...heldBack, ...applied.flatMap(result => result.heldBack.map(entry => ({ ecosystem: result.id, ...entry })))],
  notes,
  inventoryMissing,
}

if (!summary.committed.length) {
  log('Nothing was committed, so there is nothing to verify')
  return summary
}

phase('Verify')
const [gate, ...reviews] = await parallel([
  () => agent(gatePrompt(prepared), { label: 'gate', phase: 'Verify', schema: GATE, model: 'sonnet' }),
  ...LENSES.map(lens => () =>
    agent(reviewPrompt(prepared, lens), { label: `review:${lens.key}`, phase: 'Verify', schema: FINDINGS, ...lens.options })
      .then(result => result && result.findings.map(finding => ({ lens: lens.key, ...finding })))),
])

const findings = reviews.filter(Boolean).flat()
const findingsToFix = findings.filter(finding => finding.severity !== 'note')
let fix = null
let finalGate = gate

if (!gate?.green || findingsToFix.length) {
  fix = await agent(fixPrompt(prepared, gate, findingsToFix), { label: 'fix', phase: 'Verify', schema: FIX })
  finalGate = await agent(gatePrompt(prepared), { label: 'gate:after-fix', phase: 'Verify', schema: GATE, model: 'sonnet' })
}

return { ...summary, firstGate: gate, finalGate, findings, fix }
