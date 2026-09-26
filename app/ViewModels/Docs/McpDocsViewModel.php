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
        return "{$this->instanceAddress()}/api/mcp";
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
                ['name' => 'list-template-versions', 'description' => 'Lists the versions of a template.'],
            ]],
            ['name' => 'Entity types', 'changesData' => false, 'tools' => [
                ['name' => 'list-entity-types', 'description' => 'Lists the kinds of record your account keeps besides contacts.'],
            ]],
            ['name' => 'Relationship vocabulary', 'changesData' => false, 'tools' => [
                ['name' => 'list-relationship-types', 'description' => 'Lists the kinds of relationship your account uses, such as parent or colleague.'],
                ['name' => 'list-relationship-categories', 'description' => 'Lists the categories your account groups relationship types into.'],
            ]],
            ['name' => 'Life event categories', 'changesData' => false, 'tools' => [
                ['name' => 'list-life-event-categories', 'description' => 'Lists the categories your account files life events under.'],
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
            ['title' => 'The server cannot be reached', 'text' => 'Check that the address ends in `/api/mcp` and that your instance is reachable from the machine running the assistant.'],
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
