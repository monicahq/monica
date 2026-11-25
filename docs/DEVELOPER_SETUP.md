# Developer setup

This page takes you from an empty folder to a copy of Monica that runs on your machine, that you can
change and that you can test. It gives every command to run, in order, and says what each one does.

It has three parts:

1. [Get the application running](#get-the-application-running), which everybody needs.
2. [Check a change before you commit it](#check-a-change-before-you-commit-it), which you will use every day.
3. [Connect Claude Code](#connect-claude-code), which you need only if you work with Claude Code.

## What you need first

| Tool | Version | What it is for |
| --- | --- | --- |
| PHP | 8.4 or later. The continuous integration runs 8.5 | Runs the application |
| Composer | 2 | Installs the PHP packages and runs the project scripts |
| Bun | 1.4.4, the version `package.json` names | Installs the JavaScript packages and builds the assets |
| Git | Any recent version | Fetches the code |

You do not need a database server. A fresh copy of Monica keeps its data in an SQLite file.

PHP needs a number of extensions. Once you have the code, Composer tells you which ones are missing:

```bash
composer check-platform-reqs
```

Composer does not check two more, so check them yourself with `php -m`:

- `pdo_sqlite`, because the default database is an SQLite file.
- `pcntl`, because the log viewer that `composer run dev` starts refuses to start without it.

## Get the application running

### 1. Fetch the code

```bash
git clone https://github.com/monicahq/monica.git
```

```bash
cd monica
```

### 2. Install and build

Run this command on a fresh copy only. On a copy that already has a `.env`, it replaces the
encryption key, and everything the old key encrypted can no longer be read. It also runs the
migrations on whichever database that `.env` names.

```bash
composer setup
```

This one command runs six steps:

1. It installs the PHP packages.
2. It copies `.env.example` to `.env`, unless a `.env` is already there.
3. It writes a new encryption key in `.env`.
4. It creates the SQLite database in `database/database.sqlite` and runs every migration.
5. It installs the JavaScript packages.
6. It builds the assets.

To update a copy you already use, run `composer install`, `bun install` and the command of the next
step instead.

### 3. Prepare the storage folder and the API keys

```bash
php artisan monica:setup
```

This command links the storage folder into `public`, so that uploaded files can be shown. It creates
the keys that sign OAuth tokens, which the API and the MCP server need. It clears the compiled files
and brings the database up to date.

You can run it as often as you like. It keeps the keys that are already there. Run it again each time
you fetch new code, because that is what runs the new migrations.

### 4. Create the example account

```bash
php artisan db:seed
```

This creates an account for Monica Geller, with the cast of Friends as her contacts, so that every
screen has something to show.

The command takes several minutes, because it writes a whole life of records. When it has
finished, it prints the address and the password to sign in with. The address is
`monica.geller@centralperk.example`.

The command writes nothing when the account is already there, so running it twice is safe.

### 5. Start the application

```bash
composer run dev
```

This starts four processes in one terminal:

| Process | What it does |
| --- | --- |
| `server` | Serves the application on `http://localhost:8000` |
| `queue` | Runs the queued jobs |
| `logs` | Shows the log as it is written |
| `vite` | Rebuilds the assets each time you save a file |

Open `http://localhost:8000` and sign in with the example account. Press `Ctrl+C` to stop the four
processes.

Monica sends no email from a fresh copy. Every email is written to `storage/logs/laravel.log`
instead, and the `logs` process shows it.

### 6. Open the administration space

The administration space is where an instance is run: its accounts, its flags and its storage. It
is at `http://localhost:8000/admin`, and it answers not found to an account that is not an
administrator.

The example account is already an administrator, so you have nothing to do for it. An account you
register yourself is not one until you say so:

```bash
php artisan monica:make-administrator --email=rachel.green@fortunata.example
```

## Check a change before you commit it

```bash
composer test
```

This runs the three checks the continuous integration runs: the code style, the static analysis, and
the whole test suite in parallel. A change that passes here is very likely to pass there.

The tests use a database held in memory, so they never touch the data of your running copy.

While you work, you will more often run one check at a time:

| Command | What it does |
| --- | --- |
| `php artisan test --compact tests/Unit/Helpers/DurationTest.php` | Runs one test file |
| `composer lint` | Corrects the code style |
| `composer types:check` | Runs the static analysis |
| `php artisan monica:localize --check` | Reports the translations that are out of date, and writes nothing |

The rules a change has to follow are in `.claude/CLAUDE.md` and in `.claude/rules`: how to name a
branch, how to write a commit message, how to write a test. Read them before your first pull request.

## Connect Claude Code

The repository carries its own configuration for Claude Code: the rules, the skills, and the MCP
servers listed in `.mcp.json`. Most of it works as soon as you start Claude Code in the folder. The
steps below are the ones somebody has to do by hand, once per machine.

You need two more tools for this part: Python 3, which a project hook runs, and Node.js, which starts
the Playwright server through `npx`.

The project declares four MCP servers:

| Server | Where it runs | What you have to do |
| --- | --- | --- |
| `laravel-boost` | On your machine, from the application | Nothing |
| `playwright` | On your machine | Nothing |
| `sonarqube` | At SonarQube Cloud | Sign in, at step 5 |
| `nightwatch` | At Laravel Nightwatch | Sign in, at step 5 |

The `monica` project at SonarQube Cloud is public, so anybody can read its results. Steps 2 to 5 were
tested with a member of the `monicahq` organisation only. Laravel Nightwatch is private: signing in to
it needs a place in the Monica team. If you have neither, the application and the two servers that
run on your machine work without them.

### 1. Start Claude Code in the folder

```bash
claude
```

Claude Code asks whether you trust the folder, then whether you approve the MCP servers the project
declares. Approve them.

### 2. Install the SonarQube plugin

The project switches the plugin on, but a plugin is installed once per copy of the code. Leave Claude
Code, then run:

```bash
claude plugin install sonarqube@claude-plugins-official --scope project
```

Start Claude Code again.

### 3. Run the SonarQube integration

In Claude Code, type:

```
/sonarqube:sonar-integrate
```

This command does three things:

1. It installs the `sonar` command line tool, or updates it when you already have it.
2. It checks that the tool is signed in. When it is not, it gives you this command to run yourself,
   which opens your browser:

   ```bash
   sonar auth login -o monicahq
   ```

3. It installs two hooks that scan your prompts, and the files Claude Code reads, for secrets such as
   an API key.

The hooks go in your own Claude Code configuration, in `~/.claude`, and not in the repository. They
run in every project you open with Claude Code on that machine.

The command can end with a warning that the MCP server cannot start because Docker is not ready.
That is expected, and the next step removes its cause.

### 4. Remove the server the integration adds

The integration also registers a second MCP server named `sonarqube` in your own configuration. That
one runs in a container, so it needs Docker, and it has the same name as the server the project
already declares. Remove it and keep the one of the project:

```bash
claude mcp remove sonarqube -s user
```

Running the integration again brings that server back, so run this command again after it.

### 5. Sign in to the two remote servers

In Claude Code, type:

```
/mcp
```

Choose `sonarqube`, sign in from the browser window that opens, then do the same for `nightwatch`.

To check that it worked, ask Claude Code for the quality gate of the project. It answers with the
state of the `monica` project at SonarQube Cloud.
