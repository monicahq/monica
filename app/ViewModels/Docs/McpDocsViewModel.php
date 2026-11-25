<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use App\Helpers\FlagState;
use App\Helpers\SupportAvailability;
use App\Mcp\Servers\MonicaServer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use ReflectionClass;

class McpDocsViewModel
{
    public const string TITLE = 'Model Context Protocol (MCP)';

    public const string LEAD = 'Connect your AI assistant to Monica to find contacts, add people, and manage favourites through conversation.';

    public const string OVERVIEW = 'Monica’s MCP server gives compatible assistants access to tools that work with your account.';

    /** @var list<array{id: string, label: string}> */
    public const array SECTIONS = [
        ['id' => 'overview', 'label' => 'Overview'],
        ['id' => 'setup', 'label' => 'Set up Monica MCP'],
        ['id' => 'check', 'label' => 'Check your connection'],
        ['id' => 'access', 'label' => 'Manage access'],
        ['id' => 'templates', 'label' => 'Edit a template'],
        ['id' => 'troubleshooting', 'label' => 'Troubleshooting'],
        ['id' => 'see-also', 'label' => 'See also'],
    ];

    /** @var list<string> */
    public const array SETUP = [
        'An assistant connects in one of two ways. If it supports OAuth, give it the server address and it sends you to Monica to sign in. Monica shows the name the assistant gives itself and the address it sends you back to, and connects it only when you choose **Allow**. No key is needed.',
        'Otherwise, create an API key in **Settings → Security and access → API keys**. Under **What will use this key**, choose **AI Assistant** and give the key a recognisable name. Copy the key when it appears. Use a separate key for each assistant so you can revoke its access independently.',
    ];

    /** @var list<array{prompt: string, note: string}> */
    public const array CHECKS = [
        ['prompt' => '“List my Monica vaults.”', 'note' => 'Confirm that the returned vaults belong to your account.'],
        ['prompt' => '“Find Alice in my Personal vault.”', 'note' => 'An empty result is fine. What matters is that the call succeeds against the right account.'],
    ];

    /** @var list<string> */
    public const array ACCESS = [
        'Your assistant acts as you, through its API key or its OAuth connection. Changes made through MCP are recorded in **Settings → Logs**, under the assistant’s name.',
        'To disconnect an assistant connected through OAuth, choose **Disconnect** beside it in **Settings → Security and access → Connected assistants**. To disconnect one using a key, revoke its key in **Settings → Security and access → API keys**. Either takes effect at once.',
    ];

    /** @var list<string> */
    public const array TEMPLATES = [
        'A template changes through a draft. Its sections and fields can only change on a draft version, and nothing reaches the records it describes until the draft is published. Deleting a draft throws its changes away.',
        'A field can come back with a new id when its section had to be drafted first. Use the id the tool returns.',
    ];

    public const string TEMPLATE_EXAMPLE = 'Asked to add a Hobbies section with a text field and a date field, an assistant calls:';

    /** @var list<string> */
    public const array TEMPLATE_STEPS = [
        '`list-templates`, to find the template.',
        '`create-template-draft`, to open a draft and learn its version number.',
        '`add-template-section` with the name **Hobbies**, which returns the id of the new section.',
        '`create-template-field` twice: a text field named **Hobby**, then a date field named **Started**.',
        '`get-template-version`, to check the draft.',
        '`publish-template-version`, with a note saying what changed.',
    ];

    /** @var array{title: string, text: string} */
    public const array ACCESS_NOTICE = [
        'title' => 'Your assistant can access all your vaults',
        'text' => 'Your assistant can use every tool in every vault in your account, whether it connects with a key or through OAuth. Choosing “AI Assistant” when creating an API key only labels the key—it does not limit what your assistant can access or change.',
    ];

    public const string LAST_UPDATED = '2026-11-21';

    public const string SOURCE_URL = 'https://github.com/monicahq/monica';

    public function lastUpdated(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::LAST_UPDATED);
    }

    public function editUrl(): string
    {
        return self::SOURCE_URL.'/edit/main/resources/views/app/docs/mcp.blade.php';
    }

    public function issueUrl(): string
    {
        return self::SOURCE_URL.'/issues/new';
    }

    public function instanceAddress(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function serverAddress(): string
    {
        return "{$this->instanceAddress()}/mcp";
    }

    /**
     * @return list<array{key: string, label: string, content: list<array{type: string, text: string, tone: string, title: string, block: ?array{label: string, language: string, lines: list<array{parts: list<array{text: string, colour: string}>}>, code: string}}>}>
     */
    public function clients(): array
    {
        $url = $this->serverAddress();

        return [
            [
                'key' => 'claude-code',
                'label' => 'Claude Code',
                'content' => [
                    $this->text('Set the `MONICA_API_KEY` environment variable to your Monica API key before starting Claude Code.'),
                    $this->text('Add this configuration to your project’s `.mcp.json`. If the file already contains servers, add `monica` to the existing `mcpServers` object.'),
                    $this->code('.mcp.json', 'json', $this->json([
                        'mcpServers' => [
                            'monica' => [
                                'type' => 'http',
                                'url' => $url,
                                'headers' => ['Authorization' => 'Bearer ${MONICA_API_KEY}'],
                            ],
                        ],
                    ])),
                    $this->text('Start Claude Code from that environment, approve the project’s MCP configuration when prompted, and use `/mcp` to check the connection.'),
                ],
            ],
            [
                'key' => 'cursor',
                'label' => 'Cursor',
                'content' => [
                    $this->text('Make `MONICA_API_KEY` available in Cursor’s environment, then add this configuration to `~/.cursor/mcp.json`.'),
                    $this->code('~/.cursor/mcp.json', 'json', $this->json([
                        'mcpServers' => [
                            'monica' => [
                                'url' => $url,
                                'headers' => ['Authorization' => 'Bearer ${env:MONICA_API_KEY}'],
                            ],
                        ],
                    ])),
                    $this->text('Cursor reads the variable as `${env:MONICA_API_KEY}`, with the `env:` prefix. Restart Cursor after changing its environment, then check that Monica appears in its MCP settings.'),
                ],
            ],
            [
                'key' => 'codex',
                'label' => 'Codex',
                'content' => [
                    $this->text('Set `MONICA_API_KEY` in the environment where you run Codex, then run:'),
                    $this->code('Terminal', 'bash', [
                        'codex mcp add monica \\',
                        "  --url {$url} \\",
                        '  --bearer-token-env-var MONICA_API_KEY',
                    ]),
                    $this->text('Or add the server to `~/.codex/config.toml`:'),
                    $this->code('~/.codex/config.toml', 'toml', [
                        '[mcp_servers.monica]',
                        "url = \"{$url}\"",
                        'bearer_token_env_var = "MONICA_API_KEY"',
                    ]),
                    $this->text('Restart Codex after configuration. If you use the desktop app, make sure the variable is available to the app’s process, not only to a terminal.'),
                ],
            ],
            [
                'key' => 'vscode',
                'label' => 'VS Code',
                'content' => [
                    $this->text('Run **MCP: Open User Configuration** from the Command Palette and add:'),
                    $this->code('User configuration', 'json', $this->json([
                        'inputs' => [
                            [
                                'type' => 'promptString',
                                'id' => 'monica-api-key',
                                'description' => 'Monica API key',
                                'password' => true,
                            ],
                        ],
                        'servers' => [
                            'monica' => [
                                'type' => 'http',
                                'url' => $url,
                                'headers' => ['Authorization' => 'Bearer ${input:monica-api-key}'],
                            ],
                        ],
                    ])),
                    $this->text('Start the server and enter your API key when prompted. VS Code stores it securely.'),
                    $this->notice('info', 'Not available in Agent Host', 'VS Code does not forward a server that asks for input to Agent Host sessions, so this configuration does not reach them.'),
                ],
            ],
            [
                'key' => 'claude-desktop',
                'label' => 'Claude Desktop',
                'content' => [
                    $this->text('Add a custom connector with the server address below. Claude opens Monica in your browser: sign in if you are asked to, check the name and the address on the screen, and choose **Allow**. No API key is needed.'),
                    $this->code('Server address', 'text', [$url]),
                ],
            ],
            [
                'key' => 'chatgpt',
                'label' => 'ChatGPT',
                'content' => [
                    $this->text('Add a custom connector with the server address below and choose OAuth as its authentication. ChatGPT opens Monica in your browser: sign in if you are asked to, check the name and the address on the screen, and choose **Allow**. Whether custom connectors are offered depends on your ChatGPT plan and settings.'),
                    $this->code('Server address', 'text', [$url]),
                ],
            ],
            [
                'key' => 'other',
                'label' => 'Other',
                'content' => [
                    $this->text('Use a client that supports remote HTTP MCP servers. If it supports OAuth, give it the server address alone and allow it when Monica asks. Otherwise, send an API key in a header:'),
                    $this->code('Connection details', 'text', [
                        "Server URL: {$url}",
                        'Header: Authorization: Bearer YOUR_API_KEY',
                    ]),
                ],
            ],
        ];
    }

    /** @var array<string, string> */
    private const array FAMILY_SUMMARIES = [
        'Vaults' => 'A vault is a compartment for one part of your life, holding its own contacts and reminders. Almost every other tool works inside one vault, named by its id.',
        'Search' => 'Search finds the people, other records and life events of one vault, or of every vault of your account, from a few words.',
        'Contacts' => 'A contact is a person you record. Each has a page of their own, shaped by a template, holding what you know about them.',
        'Reminders' => 'A reminder is a rule telling Monica to tell you about something, on a day and at a time. Each day it falls on is a date you answer.',
        'Relationships' => 'A relationship is how two people are connected, such as a friend, a sibling or a parent and a child.',
        'Life events' => 'A life event is something that happened on a date, often with other people, such as a wedding, a move or a new job. Life events form the timeline of a contact.',
        'Groups' => 'A group gathers contacts of one vault that belong together, such as a household, a couple or a team, with its own notes, files and life events.',
        'Templates' => 'A template is the shape of a contact’s page: its pages, sections and fields. It changes through a draft and is published in versions.',
        'Entity types' => 'An entity type is a kind of record that is not a person, such as an animal or a company. Contacts are the kind every vault keeps.',
        'Entities' => 'An entity is one record of an entity type, such as one animal, kept in a vault. Its values follow the template version it was written with, which a correction never changes.',
        'Relationship vocabulary' => 'A relationship type is the word for how two people are connected, such as Sibling or Friend, filed under a category such as Family.',
        'Life event vocabulary' => 'A life event is something that happened on a date, often with other people, such as a wedding, a move or a new job. A life event type names that kind of thing, sorted into categories, and carries the fields you fill in each time.',
        'Locations' => 'A location is a place where you keep things: a house, a room, a shelf, a box. A location can sit inside another, so they form a tree.',
        'Possession types' => 'A possession type is a kind of thing you own, such as books or cars. It says which details a thing of that kind carries.',
        'Possessions' => 'A possession is something you own, recorded in one vault. It is of one type, may be kept at a location and may carry tags.',
        'Possession tags' => 'A possession tag is a word you put on the things you own, such as Insured or To sell, to sort them across their types and locations.',
        'Possession conditions' => 'A possession condition says what state a thing you own is in, such as New, Used or Broken.',
        'Knowledge' => 'Knowledge is what you want to remember that is not a person or a possession, kept in folders as notes and lists inside a vault.',
        'Account logs' => 'The account logs record every action that touched your account, and every email Monica sent you.',
        'Support' => 'A support request is a message to the people who run your Monica, about something broken, confusing or missing.',
        'Support queue' => 'The support queue holds the support requests of every account of the instance. Only an administrator of the instance can use it.',
        'Feedback' => 'Feedback is how an assistant tells the people who run your Monica that a tool failed, was missing or misled it, with what it tried and the fix it suggests. It paraphrases your request and leaves the details of your vaults out, and you never see the report in Monica, only in your MCP history.',
        'Agent feedback queue' => 'The agent feedback queue holds the reports assistants sent with submit-feedback, from every account of the instance. Only an administrator of the instance can use it.',
    ];

    /** @var array<string, list<string>> */
    public const array FAMILIES = [
        'Vaults' => ['list-vaults'],
        'Search' => ['search-records'],
        'Contacts' => ['list-contacts', 'list-contact-summaries', 'list-contact-categories', 'get-contact', 'get-contact-markdown', 'get-me-contact', 'list-contact-name-representations', 'create-contact', 'update-contact-name', 'update-contact-name-representations', 'update-contact-section', 'preview-contact-template-change', 'change-contact-template', 'set-contact-favourite', 'delete-contact', 'get-contact-list-columns', 'set-contact-list-columns'],
        'Reminders' => ['list-vault-reminders', 'list-vault-reminder-occurrences', 'list-contact-reminders', 'get-contact-reminder', 'list-contact-reminder-occurrences', 'list-group-reminders', 'get-group-reminder', 'list-group-reminder-occurrences', 'create-contact-reminder', 'update-contact-reminder', 'delete-contact-reminder', 'answer-reminder-occurrence', 'create-group-reminder', 'update-group-reminder', 'delete-group-reminder', 'answer-group-reminder-occurrence'],
        'Relationships' => ['list-contact-relationships', 'get-contact-relationship', 'create-contact-relationship', 'update-contact-relationship', 'delete-contact-relationship'],
        'Life events' => ['list-life-events', 'list-life-event-slices', 'get-life-event', 'create-life-event', 'update-life-event', 'delete-life-event', 'restore-life-event'],
        'Groups' => ['list-groups', 'get-group', 'list-group-members', 'list-contact-groups', 'list-group-files', 'list-group-notes', 'get-group-note', 'list-group-life-events', 'create-group', 'update-group', 'delete-group', 'restore-group', 'add-group-member', 'update-group-member', 'remove-group-member', 'remove-group-file', 'create-group-note', 'update-group-note', 'delete-group-note', 'create-group-life-event'],
        'Templates' => ['list-templates', 'get-template', 'list-template-versions', 'get-template-version', 'list-value-lists', 'create-value-list', 'update-value-list', 'delete-value-list', 'create-value-list-item', 'update-value-list-item', 'delete-value-list-item', 'set-value-list-item-activation', 'set-value-list-item-position', 'list-countries', 'create-template', 'update-template', 'duplicate-template', 'set-template-activation', 'set-template-position', 'delete-template', 'create-template-draft', 'publish-template-version', 'delete-template-draft', 'add-template-section', 'update-template-section', 'remove-template-section', 'set-template-section-position', 'create-template-field', 'update-template-field', 'delete-template-field', 'set-template-field-position'],
        'Entity types' => ['list-entity-types', 'get-entity-type', 'create-entity-type', 'update-entity-type', 'set-entity-type-activation', 'set-entity-type-position', 'delete-entity-type'],
        'Entities' => ['list-entities', 'get-entity', 'create-entity', 'update-entity'],
        'Relationship vocabulary' => ['list-relationship-types', 'get-relationship-type', 'list-relationship-categories', 'get-relationship-category', 'list-relationship-category-types', 'create-relationship-type', 'update-relationship-type', 'set-relationship-type-activation', 'set-relationship-type-position', 'delete-relationship-type', 'create-relationship-category', 'update-relationship-category', 'set-relationship-category-activation', 'set-relationship-category-position', 'delete-relationship-category'],
        'Life event vocabulary' => ['list-life-event-categories', 'get-life-event-category', 'list-life-event-category-types', 'list-life-event-types', 'get-life-event-type', 'get-life-event-type-fields', 'create-life-event-type', 'update-life-event-type', 'set-life-event-type-activation', 'set-life-event-type-position', 'replace-life-event-type-fields', 'delete-life-event-type', 'create-life-event-category', 'update-life-event-category', 'set-life-event-category-activation', 'set-life-event-category-position', 'delete-life-event-category'],
        'Locations' => ['list-locations', 'get-location', 'create-location', 'update-location', 'delete-location'],
        'Possession types' => ['list-possession-types', 'get-possession-type', 'export-possession-type', 'create-possession-type', 'update-possession-type', 'delete-possession-type', 'import-possession-type', 'create-possession-field-group', 'update-possession-field-group', 'delete-possession-field-group', 'set-possession-field-group-position', 'create-possession-field', 'update-possession-field', 'delete-possession-field', 'set-possession-field-position'],
        'Possessions' => ['list-possessions', 'get-possession', 'list-possession-transactions', 'get-possession-transaction', 'list-possession-insurance-records', 'get-possession-insurance-record', 'list-possession-maintenance-records', 'get-possession-maintenance-record', 'list-possession-valuations', 'get-possession-valuation', 'list-possession-provenance-events', 'get-possession-provenance-event', 'list-possession-location-stays', 'get-possession-location-stay', 'create-possession', 'update-possession', 'delete-possession', 'set-possession-position', 'set-possession-values', 'create-possession-transaction', 'update-possession-transaction', 'delete-possession-transaction', 'create-possession-insurance-record', 'update-possession-insurance-record', 'delete-possession-insurance-record', 'create-possession-maintenance-record', 'update-possession-maintenance-record', 'delete-possession-maintenance-record', 'create-possession-valuation', 'delete-possession-valuation', 'create-possession-provenance-event', 'update-possession-provenance-event', 'delete-possession-provenance-event', 'move-possession', 'update-possession-location-stay', 'delete-possession-location-stay', 'list-possession-loans', 'get-possession-loan', 'create-possession-loan', 'update-possession-loan', 'delete-possession-loan'],
        'Possession tags' => ['list-possession-tags', 'get-possession-tag', 'create-possession-tag', 'update-possession-tag', 'delete-possession-tag'],
        'Possession conditions' => ['list-possession-conditions', 'get-possession-condition', 'create-possession-condition', 'update-possession-condition', 'set-possession-condition-position', 'delete-possession-condition'],
        'Knowledge' => ['list-knowledge', 'search-knowledge', 'get-knowledge-note', 'get-knowledge-list', 'create-knowledge-folder', 'update-knowledge-folder', 'delete-knowledge-folder', 'create-knowledge-note', 'update-knowledge-note', 'delete-knowledge-note', 'create-knowledge-list', 'update-knowledge-list', 'delete-knowledge-list', 'create-knowledge-list-column', 'update-knowledge-list-column', 'delete-knowledge-list-column', 'create-knowledge-list-item', 'update-knowledge-list-item', 'delete-knowledge-list-item'],
        'Account logs' => ['list-logs', 'get-log', 'list-emails-sent', 'get-email-sent'],
        'Support' => ['list-support-requests', 'get-support-request', 'create-support-request', 'add-support-message', 'solve-support-request'],
        'Support queue' => ['list-support-queue', 'get-support-queue-request', 'send-support-reply', 'add-support-note', 'change-support-request-status'],
        'Feedback' => ['submit-feedback'],
        'Agent feedback queue' => ['list-agent-feedback', 'get-agent-feedback', 'add-agent-feedback-note', 'change-agent-feedback-status'],
    ];

    /** @var list<string> */
    private const array GROUP_REMINDER_TOOLS = ['list-group-reminders', 'get-group-reminder', 'list-group-reminder-occurrences', 'create-group-reminder', 'update-group-reminder', 'delete-group-reminder', 'answer-group-reminder-occurrence'];

    /** @var list<array{slug: string, name: string, tools: list<array{name: string, description: string, changesData: bool, params: list<array{name: string, type: string, required: bool, description: string, values: list<mixed>}>}>}>|null */
    private ?array $families = null;

    /** @return list<array{slug: string, name: string, tools: list<array{name: string, description: string, changesData: bool, params: list<array{name: string, type: string, required: bool, description: string, values: list<mixed>}>}>}> */
    public function families(): array
    {
        if ($this->families !== null) {
            return $this->families;
        }

        $catalogue = self::catalogue();
        $groups = FlagState::isOn('groups', null);
        $support = SupportAvailability::isEnabled();
        $families = [];

        foreach (self::FAMILIES as $name => $names) {
            if (in_array($name, ['Support', 'Support queue'], true) && ! $support) {
                continue;
            }

            if ($name === 'Groups' && ! $groups) {
                continue;
            }

            $tools = array_values(array_filter(
                array_map(fn (string $tool): ?array => $catalogue[$tool] ?? null, $names),
                fn (?array $tool): bool => $tool !== null && ($groups || ! in_array($tool['name'], self::GROUP_REMINDER_TOOLS, true)),
            ));

            if ($tools === []) {
                continue;
            }

            $families[] = ['slug' => Str::slug($name), 'name' => $name, 'tools' => $tools];
        }

        return $this->families = $families;
    }

    /** @return array{slug: string, name: string, tools: list<array{name: string, description: string, changesData: bool, params: list<array{name: string, type: string, required: bool, description: string, values: list<mixed>}>}>, summary: string, previous: ?array{slug: string, name: string}, next: ?array{slug: string, name: string}}|null */
    public function family(string $slug): ?array
    {
        $families = $this->families();
        $position = array_search($slug, array_column($families, 'slug'), true);

        if ($position === false) {
            return null;
        }

        $family = $families[$position];

        return [
            ...$family,
            'summary' => self::FAMILY_SUMMARIES[$family['name']],
            'previous' => $position > 0 ? ['slug' => $families[$position - 1]['slug'], 'name' => $families[$position - 1]['name']] : null,
            'next' => $position < count($families) - 1 ? ['slug' => $families[$position + 1]['slug'], 'name' => $families[$position + 1]['name']] : null,
        ];
    }

    public function toolCount(): int
    {
        return array_sum(array_map(fn (array $family): int => count($family['tools']), $this->families()));
    }

    /**
     * @return array<string, array{name: string, description: string, changesData: bool, params: list<array{name: string, type: string, required: bool, description: string, values: list<mixed>}>}>
     */
    public static function catalogue(): array
    {
        $catalogue = [];

        foreach (new ReflectionClass(MonicaServer::class)->getDefaultProperties()['tools'] as $class) {
            $tool = app($class)->toArray();
            $required = $tool['inputSchema']['required'] ?? [];
            $params = [];

            foreach ($tool['inputSchema']['properties'] ?? [] as $name => $property) {
                $type = array_values(array_diff((array) ($property['type'] ?? 'string'), ['null']));

                $params[] = [
                    'name' => (string) $name,
                    'type' => $type[0] ?? 'string',
                    'required' => in_array($name, $required, true),
                    'description' => (string) ($property['description'] ?? ''),
                    'values' => array_values(array_filter((array) ($property['enum'] ?? []), fn (mixed $value): bool => $value !== null)),
                ];
            }

            $catalogue[$tool['name']] = [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'changesData' => ! ($tool['annotations']['readOnlyHint'] ?? false),
                'params' => $params,
            ];
        }

        return $catalogue;
    }

    /** @return list<array{title: string, text: string}> */
    public function troubleshooting(): array
    {
        $limit = config('monica.self_hosted')
            ? 'This instance sets no limit on MCP requests, so the refusal comes from something in front of it, such as a proxy.'
            : 'The assistant reached the limit of 60 requests a minute for its key or its connection. Wait a minute and ask again.';

        return [
            ['title' => 'The assistant is refused', 'text' => 'With a key, the key is wrong or revoked: create a new one in **Settings → Security and access → API keys** and update your configuration. With OAuth, the assistant was disconnected or its access expired: remove the connector, add it again, and choose **Allow**.'],
            ['title' => 'The server cannot be reached', 'text' => 'Check that the address ends in `/mcp` and that your instance is reachable from the machine running the assistant.'],
            ['title' => 'The key is empty', 'text' => 'The assistant cannot see `MONICA_API_KEY`. Set it in the environment the assistant starts from, then restart it. Desktop apps often do not read your terminal’s variables.'],
            ['title' => 'Too many requests', 'text' => $limit],
        ];
    }

    /** @return list<array{label: string, url: string}> */
    public function seeAlso(): array
    {
        return [
            ['label' => 'API reference', 'url' => route('docs.api.index')],
            ['label' => 'API keys and connected assistants', 'url' => route('settings.security.index')],
            ['label' => 'Logs', 'url' => route('settings.logs.index')],
        ];
    }

    public function markdown(): string
    {
        $lines = [
            '# '.self::TITLE,
            '',
            self::LEAD,
            '',
            self::OVERVIEW,
            '',
            "Server address: `{$this->serverAddress()}`",
            '',
            '## Set up Monica MCP',
            '',
            ...$this->paragraphs(self::SETUP),
        ];

        foreach ($this->clients() as $client) {
            $lines[] = "### {$client['label']}";
            $lines[] = '';

            foreach ($client['content'] as $item) {
                if ($item['block'] !== null) {
                    $lines[] = "```{$item['block']['language']}";
                    $lines[] = $item['block']['code'];
                    $lines[] = '```';
                    $lines[] = '';

                    continue;
                }

                $lines[] = $item['title'] === '' ? $item['text'] : "**{$item['title']}.** {$item['text']}";
                $lines[] = '';
            }
        }

        $lines[] = '## Check your connection';
        $lines[] = '';
        $lines[] = 'Ask your assistant:';
        $lines[] = '';

        foreach (self::CHECKS as $check) {
            $lines[] = "- {$check['prompt']} {$check['note']}";
        }

        $lines[] = '';
        $lines[] = '## Manage access';
        $lines[] = '';
        array_push($lines, ...$this->paragraphs(self::ACCESS));
        $lines[] = '**'.self::ACCESS_NOTICE['title'].'.** '.self::ACCESS_NOTICE['text'];
        $lines[] = '';
        $lines[] = '## Available tools';
        $lines[] = '';

        foreach ($this->families() as $family) {
            $lines[] = "### {$family['name']}";
            $lines[] = '';

            foreach ($family['tools'] as $tool) {
                array_push($lines, ...$this->toolLines($tool, '####'));
            }
        }

        $lines[] = '## Edit a template';
        $lines[] = '';
        array_push($lines, ...$this->paragraphs(self::TEMPLATES));
        $lines[] = self::TEMPLATE_EXAMPLE;
        $lines[] = '';

        foreach (self::TEMPLATE_STEPS as $number => $step) {
            $lines[] = ($number + 1).". {$step}";
        }

        $lines[] = '';
        $lines[] = '## Troubleshooting';
        $lines[] = '';

        foreach ($this->troubleshooting() as $entry) {
            $lines[] = "- **{$entry['title']}.** {$entry['text']}";
        }

        $lines[] = '';
        $lines[] = '## See also';
        $lines[] = '';

        foreach ($this->seeAlso() as $link) {
            $lines[] = "- [{$link['label']}]({$link['url']})";
        }

        return implode("\n", $lines)."\n";
    }

    /** @param array{name: string, description: string, changesData: bool, params: list<array{name: string, type: string, required: bool, description: string, values: list<mixed>}>} $tool */
    public function toolMarkdown(array $tool): string
    {
        return rtrim(implode("\n", $this->toolLines($tool, '##')))."\n";
    }

    /**
     * @param  array{name: string, description: string, changesData: bool, params: list<array{name: string, type: string, required: bool, description: string, values: list<mixed>}>}  $tool
     * @return list<string>
     */
    private function toolLines(array $tool, string $heading): array
    {
        $mark = $tool['changesData'] ? ' (changes data)' : '';
        $lines = ["{$heading} `{$tool['name']}`{$mark}", '', $tool['description'], ''];

        foreach ($tool['params'] as $param) {
            $need = $param['required'] ? 'required' : 'optional';
            $values = $param['values'] === [] ? '' : ' One of: '.implode(', ', array_map(fn (mixed $value): string => "`{$value}`", $param['values'])).'.';
            $lines[] = "- `{$param['name']}` ({$param['type']}, {$need}): {$param['description']}{$values}";
        }

        if ($tool['params'] !== []) {
            $lines[] = '';
        }

        return $lines;
    }

    /** @return array{type: string, text: string, tone: string, title: string, block: null} */
    private function text(string $text): array
    {
        return ['type' => 'text', 'text' => $text, 'tone' => '', 'title' => '', 'block' => null];
    }

    /** @return array{type: string, text: string, tone: string, title: string, block: null} */
    private function notice(string $tone, string $title, string $text): array
    {
        return ['type' => 'notice', 'text' => $text, 'tone' => $tone, 'title' => $title, 'block' => null];
    }

    /**
     * @param  list<string>  $lines
     * @return array{type: string, text: string, tone: string, title: string, block: array{label: string, language: string, lines: list<array{parts: list<array{text: string, colour: string}>}>, code: string}}
     */
    private function code(string $label, string $language, array $lines): array
    {
        return [
            'type' => 'code',
            'text' => '',
            'tone' => '',
            'title' => '',
            'block' => [
                'label' => $label,
                'language' => $language,
                'lines' => array_map(ApiDocsCodeSamples::highlight(...), $lines),
                'code' => implode("\n", $lines),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $value
     * @return list<string>
     */
    private function json(array $value): array
    {
        return explode("\n", str_replace('    ', '  ', (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
    }

    /**
     * @param  list<string>  $paragraphs
     * @return list<string>
     */
    private function paragraphs(array $paragraphs): array
    {
        $lines = [];

        foreach ($paragraphs as $paragraph) {
            $lines[] = $paragraph;
            $lines[] = '';
        }

        return $lines;
    }
}
