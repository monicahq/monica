<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

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
        ['id' => 'tools', 'label' => 'Available tools'],
        ['id' => 'templates', 'label' => 'Edit a template'],
        ['id' => 'entity-types', 'label' => 'Manage entity types'],
        ['id' => 'relationship-types', 'label' => 'Manage relationship types'],
        ['id' => 'life-event-types', 'label' => 'Manage life event types'],
        ['id' => 'logs', 'label' => 'Read the logs'],
        ['id' => 'troubleshooting', 'label' => 'Troubleshooting'],
        ['id' => 'see-also', 'label' => 'See also'],
    ];

    /** @var list<string> */
    public const array SETUP = [
        'Create an API key in **Settings → Security and access → API keys**. Under **What will use this key**, choose **AI Assistant** and give the key a recognisable name.',
        'Copy the key when it appears. Use a separate key for each assistant so you can revoke its access independently.',
    ];

    /** @var list<array{prompt: string, note: string}> */
    public const array CHECKS = [
        ['prompt' => '“List my Monica vaults.”', 'note' => 'Confirm that the returned vaults belong to your account.'],
        ['prompt' => '“Find Alice in my Personal vault.”', 'note' => 'An empty result is fine. What matters is that the call succeeds against the right account.'],
    ];

    /** @var list<string> */
    public const array ACCESS = [
        'Your assistant acts through its API key. Changes made through MCP are recorded in **Settings → Logs**.',
        'To disconnect an assistant, revoke its key in **Settings → Security and access → API keys**.',
    ];

    /** @var list<string> */
    public const array TEMPLATES = [
        'A template changes through a draft. Its sections and fields can only change on a draft version, and nothing reaches the records it describes until the draft is published. Deleting a draft throws its changes away.',
        'The tools that change a template, a section or a field keep what you leave out, so asking for one change never clears the others. A field can come back with a new id when its section had to be drafted first. Use the id the tool returns.',
    ];

    /** @var list<string> */
    public const array ENTITY_TYPES = [
        'An entity type is a kind of record your account keeps besides contacts, such as Animal or Wine bottle. Its records follow a template, created with `create-template` and `applies_to` set to `entity`.',
        'A type still holding records cannot be deleted. Switch it off with `set-entity-type-activation` instead: its records stay, and no new one can be added.',
    ];

    /** @var list<string> */
    public const array RELATIONSHIP_TYPES = [
        'A relationship type names how two contacts are linked. It gives each side a role, such as parent and child, or the same role to both, such as siblings, and it is filed under a category such as Family or Work.',
        'Types and categories that came with your account, and any that are in use, cannot be deleted. Stop offering them with `set-relationship-type-activation` or `set-relationship-category-activation` instead: what is already written with them stays.',
    ];

    /** @var list<string> */
    public const array LIFE_EVENT_TYPES = [
        'A life event type is a kind of thing that happens to a contact, such as Moved house, filed under a category such as Work or Home. It can ask for fields of its own, set with `replace-life-event-type-fields`, which replaces the whole set and never changes a life event already recorded.',
        'Types and categories that came with your account, and any that are in use, cannot be deleted. Stop offering them with `set-life-event-type-activation` or `set-life-event-category-activation` instead: the life events already recorded stay.',
    ];

    /** @var list<string> */
    public const array LOGS = [
        'The activity lists every change made in your account, newest first: who made it, what it was, where it came from, such as the web, the API or an assistant, and the address it came from. A change made through MCP is marked as coming from an assistant, so you can ask your assistant what it did and check the answer against **Settings → Logs**.',
        'The emails sent list what Monica sent your account and whether it arrived. Reading one returns what it said, with every link and web address removed before it was kept, so a sign-in link can never be read back from the logs.',
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
        'title' => 'Assistant is a label, not a limit',
        'text' => 'Choosing AI Assistant identifies the key in your list. It does not reduce what the key can do: an assistant can use every tool below on every vault in your account.',
    ];

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
                    $this->notice('warning', 'Request headers are in beta', 'Claude Desktop can send an API key only through the request headers of a custom connector, a beta available to a limited set of organisations. If you see **Request headers** when you add a custom connector, enter the server address, choose **No sign-in**, and add the header `Authorization` with the value `Bearer YOUR_API_KEY`. Otherwise, use Claude Code or another client listed here.'),
                ],
            ],
            [
                'key' => 'chatgpt',
                'label' => 'ChatGPT',
                'content' => [
                    $this->notice('quiet', 'Not available yet', 'ChatGPT connects to authenticated MCP servers with OAuth and does not accept custom API keys. Monica does not offer OAuth for MCP yet, so ChatGPT cannot connect to it directly.'),
                ],
            ],
            [
                'key' => 'other',
                'label' => 'Other',
                'content' => [
                    $this->text('Use a client that supports remote HTTP MCP servers and custom authentication headers:'),
                    $this->code('Connection details', 'text', [
                        "Server URL: {$url}",
                        'Header: Authorization: Bearer YOUR_API_KEY',
                    ]),
                ],
            ],
        ];
    }

    /** @return list<array{name: string, changesData: bool, tools: list<array{name: string, description: string}>}> */
    public function toolGroups(): array
    {
        return [
            ['name' => 'Vaults', 'changesData' => false, 'tools' => [
                ['name' => 'list-vaults', 'description' => 'Lists the vaults of your account.'],
            ]],
            ['name' => 'Contacts, reading', 'changesData' => false, 'tools' => [
                ['name' => 'list-contacts', 'description' => 'Lists the contacts in a vault. Can search, narrow to a grouping, and sort.'],
                ['name' => 'get-contact', 'description' => 'Returns one contact: their names, their template, and whether they are a favourite or archived.'],
                ['name' => 'get-me-contact', 'description' => 'Returns the contact that represents you in a vault.'],
            ]],
            ['name' => 'Contacts, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-contact', 'description' => 'Adds a contact to a vault.'],
                ['name' => 'update-contact-name', 'description' => 'Changes a contact’s first, middle or last name, or nickname.'],
                ['name' => 'set-contact-favourite', 'description' => 'Marks a contact as a favourite, or removes the mark.'],
            ]],
            ['name' => 'Templates', 'changesData' => false, 'tools' => [
                ['name' => 'list-templates', 'description' => 'Lists the templates of your account.'],
                ['name' => 'get-template', 'description' => 'Returns one template: its name, what it describes, whether it is active and which version is published.'],
                ['name' => 'list-template-versions', 'description' => 'Lists the versions of a template.'],
                ['name' => 'get-template-version', 'description' => 'Returns one version of a template with its pages, sections and fields, and the ids the other template tools take.'],
            ]],
            ['name' => 'Templates, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-template', 'description' => 'Creates a template for contacts, for a kind of entity or for life events.'],
                ['name' => 'update-template', 'description' => 'Renames a template or changes its description.'],
                ['name' => 'duplicate-template', 'description' => 'Copies a template into a new one.'],
                ['name' => 'set-template-activation', 'description' => 'Switches a template on or off for new records.'],
                ['name' => 'set-template-position', 'description' => 'Moves a template to another place in the list.'],
                ['name' => 'delete-template', 'description' => 'Deletes a template that no record uses.'],
            ]],
            ['name' => 'Template drafts', 'changesData' => true, 'tools' => [
                ['name' => 'create-template-draft', 'description' => 'Opens a draft of a template, or returns the one already open.'],
                ['name' => 'publish-template-version', 'description' => 'Publishes a draft, so the records the template describes start using it.'],
                ['name' => 'delete-template-draft', 'description' => 'Deletes a draft and every change made in it.'],
            ]],
            ['name' => 'Sections', 'changesData' => true, 'tools' => [
                ['name' => 'add-template-section', 'description' => 'Adds a new section to a draft, or one your account already has.'],
                ['name' => 'update-template-section', 'description' => 'Changes what a section is headed in a draft and whether it starts folded shut.'],
                ['name' => 'remove-template-section', 'description' => 'Removes a section from a draft. The section stays in your account.'],
                ['name' => 'set-template-section-position', 'description' => 'Moves a section to another place on its page.'],
            ]],
            ['name' => 'Fields', 'changesData' => true, 'tools' => [
                ['name' => 'create-template-field', 'description' => 'Adds a field to a section of a draft.'],
                ['name' => 'update-template-field', 'description' => 'Changes a field. What you leave out keeps its value.'],
                ['name' => 'delete-template-field', 'description' => 'Deletes a field from a section of a draft.'],
                ['name' => 'set-template-field-position', 'description' => 'Moves a field to another place in its section.'],
            ]],
            ['name' => 'Entity types', 'changesData' => false, 'tools' => [
                ['name' => 'list-entity-types', 'description' => 'Lists the kinds of record your account keeps besides contacts.'],
                ['name' => 'get-entity-type', 'description' => 'Returns one kind of record: its names, icon, colour, whether new records can be added and how many exist.'],
            ]],
            ['name' => 'Entity types, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-entity-type', 'description' => 'Adds a kind of record, such as Animal or Wine bottle.'],
                ['name' => 'update-entity-type', 'description' => 'Renames a kind of record or changes its description, icon or colour. What you leave out keeps its value.'],
                ['name' => 'set-entity-type-activation', 'description' => 'Switches a kind of record on or off for new records. Existing ones stay.'],
                ['name' => 'set-entity-type-position', 'description' => 'Moves a kind of record to another place in the list.'],
                ['name' => 'delete-entity-type', 'description' => 'Deletes a kind of record that holds no record.'],
            ]],
            ['name' => 'Relationship vocabulary', 'changesData' => false, 'tools' => [
                ['name' => 'list-relationship-types', 'description' => 'Lists the kinds of relationship your account uses, such as parent or colleague.'],
                ['name' => 'get-relationship-type', 'description' => 'Returns one kind of relationship: its roles, its category, whether it is offered and how many relationships use it.'],
                ['name' => 'list-relationship-categories', 'description' => 'Lists the categories your account groups relationship types into.'],
                ['name' => 'get-relationship-category', 'description' => 'Returns one category and how many types it holds.'],
                ['name' => 'list-relationship-category-types', 'description' => 'Lists the kinds of relationship filed under one category.'],
            ]],
            ['name' => 'Relationship vocabulary, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-relationship-type', 'description' => 'Adds a kind of relationship, such as godparent and godchild.'],
                ['name' => 'update-relationship-type', 'description' => 'Renames a kind of relationship, changes its roles or moves it to another category. What you leave out keeps its value.'],
                ['name' => 'set-relationship-type-activation', 'description' => 'Offers a kind of relationship when contacts are linked, or stops offering it. Existing relationships stay.'],
                ['name' => 'set-relationship-type-position', 'description' => 'Moves a kind of relationship within its category.'],
                ['name' => 'delete-relationship-type', 'description' => 'Deletes a kind of relationship you created and nobody uses.'],
                ['name' => 'create-relationship-category', 'description' => 'Adds a category, such as Sports.'],
                ['name' => 'update-relationship-category', 'description' => 'Renames a category you created.'],
                ['name' => 'set-relationship-category-activation', 'description' => 'Offers a category or stops offering it.'],
                ['name' => 'set-relationship-category-position', 'description' => 'Moves a category to another place in the list.'],
                ['name' => 'delete-relationship-category', 'description' => 'Deletes a category you created that holds no type.'],
            ]],
            ['name' => 'Life event vocabulary', 'changesData' => false, 'tools' => [
                ['name' => 'list-life-event-categories', 'description' => 'Lists the categories your account files life events under.'],
                ['name' => 'get-life-event-category', 'description' => 'Returns one category and how many types it holds.'],
                ['name' => 'list-life-event-category-types', 'description' => 'Lists the kinds of life event filed under one category.'],
                ['name' => 'list-life-event-types', 'description' => 'Lists the kinds of life event your account can record, such as Moved house.'],
                ['name' => 'get-life-event-type', 'description' => 'Returns one kind of life event: its icon, colour, category and whether it is offered.'],
                ['name' => 'get-life-event-type-fields', 'description' => 'Returns the fields a kind of life event asks for, in their groups.'],
            ]],
            ['name' => 'Life event vocabulary, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-life-event-type', 'description' => 'Adds a kind of life event, such as Ran a marathon.'],
                ['name' => 'update-life-event-type', 'description' => 'Renames a kind of life event, changes its description, icon or colour, or moves it to another category. What you leave out keeps its value.'],
                ['name' => 'set-life-event-type-activation', 'description' => 'Offers a kind of life event, or stops offering it. Life events already recorded stay.'],
                ['name' => 'set-life-event-type-position', 'description' => 'Moves a kind of life event within its category.'],
                ['name' => 'replace-life-event-type-fields', 'description' => 'Replaces the fields a kind of life event asks for. A field you leave out is no longer asked for; life events already recorded keep theirs.'],
                ['name' => 'delete-life-event-type', 'description' => 'Deletes a kind of life event you created and nobody used.'],
                ['name' => 'create-life-event-category', 'description' => 'Adds a category, such as Sport.'],
                ['name' => 'update-life-event-category', 'description' => 'Renames a category.'],
                ['name' => 'set-life-event-category-activation', 'description' => 'Offers a category or stops offering it.'],
                ['name' => 'set-life-event-category-position', 'description' => 'Moves a category to another place in the list.'],
                ['name' => 'delete-life-event-category', 'description' => 'Deletes a category you created that holds no type.'],
            ]],
            ['name' => 'Account logs', 'changesData' => false, 'tools' => [
                ['name' => 'list-logs', 'description' => 'Lists what has been done in your account, newest first, a page at a time.'],
                ['name' => 'get-log', 'description' => 'Returns one entry of the activity: who, what, from where and when.'],
                ['name' => 'list-emails-sent', 'description' => 'Lists the emails sent to your account and whether each arrived, a page at a time.'],
                ['name' => 'get-email-sent', 'description' => 'Returns one email, including what it said with links and web addresses removed.'],
            ]],
        ];
    }

    /** @return list<string> */
    public function toolsSummary(): array
    {
        $count = array_sum(array_map(fn (array $group): int => count($group['tools']), $this->toolGroups()));

        return [
            "MCP currently exposes {$count} tools. Contact responses include identity and metadata, but not the contents of custom fields, notes, or life events.",
            'Our goal is to make every action available in Monica accessible through MCP.',
        ];
    }

    /** @return list<array{title: string, text: string}> */
    public function troubleshooting(): array
    {
        $limit = config('monica.self_hosted')
            ? 'This instance sets no limit on MCP requests, so the refusal comes from something in front of it, such as a proxy.'
            : 'The assistant reached the limit of 60 requests a minute for its key. Wait a minute and ask again.';

        return [
            ['title' => 'The assistant is refused', 'text' => 'The key is wrong or revoked. Create a new one in **Settings → Security and access → API keys** and update your configuration.'],
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
            ['label' => 'API keys', 'url' => route('settings.security.index')],
            ['label' => 'Logs', 'url' => route('settings.logs.index')],
        ];
    }

    /** @return array{claude: string, chatgpt: string} */
    public function assistantLinks(): array
    {
        $prompt = rawurlencode('Read '.route('docs.mcp.markdown.index').' so I can ask you questions about it.');

        return [
            'claude' => "https://claude.ai/new?q={$prompt}",
            'chatgpt' => "https://chatgpt.com/?hints=search&q={$prompt}",
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

        foreach ($this->toolGroups() as $group) {
            $lines[] = $group['changesData'] ? "### {$group['name']} (changes data)" : "### {$group['name']}";
            $lines[] = '';

            foreach ($group['tools'] as $tool) {
                $lines[] = "- `{$tool['name']}`: {$tool['description']}";
            }

            $lines[] = '';
        }

        array_push($lines, ...$this->paragraphs($this->toolsSummary()));
        $lines[] = '## Edit a template';
        $lines[] = '';
        array_push($lines, ...$this->paragraphs(self::TEMPLATES));
        $lines[] = self::TEMPLATE_EXAMPLE;
        $lines[] = '';

        foreach (self::TEMPLATE_STEPS as $number => $step) {
            $lines[] = ($number + 1).". {$step}";
        }

        $lines[] = '';
        $lines[] = '## Manage entity types';
        $lines[] = '';
        array_push($lines, ...$this->paragraphs(self::ENTITY_TYPES));
        $lines[] = '## Manage relationship types';
        $lines[] = '';
        array_push($lines, ...$this->paragraphs(self::RELATIONSHIP_TYPES));
        $lines[] = '## Manage life event types';
        $lines[] = '';
        array_push($lines, ...$this->paragraphs(self::LIFE_EVENT_TYPES));
        $lines[] = '## Read the logs';
        $lines[] = '';
        array_push($lines, ...$this->paragraphs(self::LOGS));
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
