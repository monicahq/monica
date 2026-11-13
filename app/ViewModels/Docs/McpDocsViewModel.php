<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use App\Helpers\SupportAvailability;
use Carbon\CarbonImmutable;

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
        'The tools that change a template, a section or a field keep what you leave out, so asking for one change never clears the others. A field can come back with a new id when its section had to be drafted first. Use the id the tool returns.',
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
        'text' => 'Your assistant can use every tool listed below in every vault in your account, whether it connects with a key or through OAuth. Choosing “AI Assistant” when creating an API key only labels the key—it does not limit what your assistant can access or change.',
    ];

    public const string LAST_UPDATED = '2026-10-23';

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

    /** @return list<array{name: string, changesData: bool, tools: list<array{name: string, description: string}>}> */
    public function toolGroups(): array
    {
        $groups = [
            ['name' => 'Vaults', 'changesData' => false, 'tools' => [
                ['name' => 'list-vaults', 'description' => 'Lists the vaults of your account.'],
            ]],
            ['name' => 'Search', 'changesData' => false, 'tools' => [
                ['name' => 'search-records', 'description' => 'Finds the people, entities and life events of a vault, or of every vault, holding every word given, typing mistakes tolerated.'],
            ]],
            ['name' => 'Contacts, reading', 'changesData' => false, 'tools' => [
                ['name' => 'list-contacts', 'description' => 'Lists the contacts in a vault, a page at a time. Can search, narrow to a grouping, and sort.'],
                ['name' => 'get-contact', 'description' => 'Returns one contact: their names, their template, and whether they are a favourite or archived.'],
                ['name' => 'get-contact-markdown', 'description' => 'Returns everything recorded about one contact as a single Markdown document, the same text the contact page downloads.'],
                ['name' => 'get-me-contact', 'description' => 'Returns the contact that represents you in a vault.'],
                ['name' => 'list-contact-name-representations', 'description' => 'Returns every way a contact’s name is written, and which one it is shown under.'],
            ]],
            ['name' => 'Contacts, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-contact', 'description' => 'Adds a contact to a vault.'],
                ['name' => 'update-contact-name', 'description' => 'Changes a contact’s first, middle or last name, or nickname.'],
                ['name' => 'update-contact-name-representations', 'description' => 'Replaces every way a contact’s name is written, and chooses the one it is shown under.'],
                ['name' => 'set-contact-favourite', 'description' => 'Marks a contact as a favourite, or removes the mark.'],
            ]],
            ['name' => 'Reminders, reading', 'changesData' => false, 'tools' => [
                ['name' => 'list-contact-reminders', 'description' => 'Lists the reminders about a contact.'],
                ['name' => 'get-contact-reminder', 'description' => 'Returns one reminder: what it says, where its date comes from, how often it comes back and its next date.'],
                ['name' => 'list-contact-reminder-occurrences', 'description' => 'Lists the dates the reminders about a contact produced, with what was answered for each.'],
                ['name' => 'list-group-reminders', 'description' => 'Lists the reminders about a group as a whole.'],
                ['name' => 'get-group-reminder', 'description' => 'Returns one reminder about a group: what it says, how often it comes back and its next date.'],
                ['name' => 'list-group-reminder-occurrences', 'description' => 'Lists the dates the reminders about a group produced, with what was answered for each.'],
                ['name' => 'list-vault-reminder-occurrences', 'description' => 'Lists what is overdue, due today and coming up in a whole vault, or only the birthdays, with the age being reached.'],
            ]],
            ['name' => 'Reminders, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-contact-reminder', 'description' => 'Writes a reminder about a contact with a date of its own.'],
                ['name' => 'update-contact-reminder', 'description' => 'Changes what a reminder says and when it comes back.'],
                ['name' => 'delete-contact-reminder', 'description' => 'Stops a reminder for good. The answers already given stay.'],
                ['name' => 'answer-reminder-occurrence', 'description' => 'Records whether you remembered a date or forgot it. The answer is final.'],
                ['name' => 'create-group-reminder', 'description' => 'Writes a reminder about a group as a whole, with a date of its own.'],
                ['name' => 'update-group-reminder', 'description' => 'Changes what a reminder about a group says and when it comes back.'],
                ['name' => 'delete-group-reminder', 'description' => 'Stops a reminder about a group for good. The answers already given stay.'],
                ['name' => 'answer-group-reminder-occurrence', 'description' => 'Records whether you remembered a date of a reminder about a group or forgot it. The answer is final.'],
            ]],
            ['name' => 'Relationships, reading', 'changesData' => false, 'tools' => [
                ['name' => 'list-contact-relationships', 'description' => 'Lists the people a contact is linked to, with the role each holds.'],
                ['name' => 'get-contact-relationship', 'description' => 'Returns one relationship: its type, both people, whether it is current or ended, and its dates.'],
            ]],
            ['name' => 'Relationships, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-contact-relationship', 'description' => 'Links a contact to somebody else, such as a partner or a parent. The other person can be somebody not written down yet.'],
                ['name' => 'update-contact-relationship', 'description' => 'Changes a relationship’s type, roles, dates or whether it ended. What you leave out keeps its value.'],
                ['name' => 'delete-contact-relationship', 'description' => 'Deletes a relationship. Both people stay.'],
            ]],
            ['name' => 'Life events, reading', 'changesData' => false, 'tools' => [
                ['name' => 'list-life-events', 'description' => 'Lists the life events of a vault, the most recent first. Can narrow to the ones one contact took part in.'],
                ['name' => 'get-life-event', 'description' => 'Returns one life event: its type, its date, who was there and the values of its custom fields.'],
            ]],
            ['name' => 'Life events, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-life-event', 'description' => 'Writes down something that happened, with the people who were there and the values of its custom fields.'],
                ['name' => 'update-life-event', 'description' => 'Changes a life event. What is left out keeps its value.'],
                ['name' => 'delete-life-event', 'description' => 'Takes a life event off every timeline. It can be restored.'],
                ['name' => 'restore-life-event', 'description' => 'Puts a deleted life event back.'],
            ]],
            ['name' => 'Groups, reading', 'changesData' => false, 'tools' => [
                ['name' => 'list-groups', 'description' => 'Lists the groups of a vault, such as households and teams, with how many people are in each today.'],
                ['name' => 'get-group', 'description' => 'Returns one group: its name, its description, its dates and how many people are in it today.'],
                ['name' => 'list-group-members', 'description' => 'Lists the people of a group, those in it today first, each with their role and dates.'],
                ['name' => 'list-contact-groups', 'description' => 'Lists the groups a contact belongs to or belonged to, with their role and dates in each.'],
                ['name' => 'list-group-files', 'description' => 'Lists the files kept with a group, the newest first, with their name, type and size. Needs groups and file upload.'],
                ['name' => 'list-group-notes', 'description' => 'Lists the notes written about a group, the newest first.'],
                ['name' => 'get-group-note', 'description' => 'Returns one note written about a group.'],
                ['name' => 'list-group-life-events', 'description' => 'Lists the activities recorded on a group, the most recent first. Needs groups.'],
            ]],
            ['name' => 'Groups, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-group', 'description' => 'Creates a group of contacts in a vault, such as a household or a team. It starts with nobody in it.'],
                ['name' => 'update-group', 'description' => 'Changes the name, the description or the dates of a group. What you leave out keeps its value.'],
                ['name' => 'delete-group', 'description' => 'Deletes a group. The people in it stay, and it can be restored with its memberships.'],
                ['name' => 'restore-group', 'description' => 'Puts a deleted group back, with every membership it held.'],
                ['name' => 'add-group-member', 'description' => 'Puts a contact in a group, with an optional role and the dates they joined and left.'],
                ['name' => 'update-group-member', 'description' => 'Changes the role or the dates of a membership. Giving a leave date is how somebody leaves.'],
                ['name' => 'remove-group-member', 'description' => 'Deletes a membership recorded by mistake. Somebody who really left gets a leave date instead.'],
                ['name' => 'remove-group-file', 'description' => 'Stops keeping a file with a group. The file stays wherever else it is used. Needs groups and file upload.'],
                ['name' => 'create-group-note', 'description' => 'Writes a note about a group as a whole. It never reaches the page of a member.'],
                ['name' => 'update-group-note', 'description' => 'Changes what a note about a group says. It keeps the day it was written.'],
                ['name' => 'delete-group-note', 'description' => 'Deletes a note about a group. Nothing else changes.'],
                ['name' => 'create-group-life-event', 'description' => 'Records an activity on a group, with the members who were there. It goes on the timeline of each of them. Needs groups.'],
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
            ['name' => 'Locations', 'changesData' => false, 'tools' => [
                ['name' => 'list-locations', 'description' => 'Lists the places of your account, such as a house, a room or a shelf, each before the places inside it, with its full path.'],
                ['name' => 'get-location', 'description' => 'Returns one place: its path, its emoji, its address or the one it falls back on, and how many places it holds.'],
            ]],
            ['name' => 'Locations, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-location', 'description' => 'Adds a place, at the top level or inside another place.'],
                ['name' => 'update-location', 'description' => 'Renames a place, moves it with everything inside it, or changes its emoji or address. What you leave out keeps its value.'],
                ['name' => 'delete-location', 'description' => 'Deletes a place that holds no other place.'],
            ]],
            ['name' => 'Possession types', 'changesData' => false, 'tools' => [
                ['name' => 'list-possession-types', 'description' => 'Lists the kinds of things your account owns, with their groups and fields in order.'],
                ['name' => 'get-possession-type', 'description' => 'Returns one possession type: its colour, its groups and its fields, each with its kind and choices.'],
                ['name' => 'export-possession-type', 'description' => 'Writes a possession type out as a file you can import again.'],
            ]],
            ['name' => 'Possession types, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-possession-type', 'description' => 'Adds a kind of thing you own, such as office equipment.'],
                ['name' => 'update-possession-type', 'description' => 'Renames a possession type or changes its colour. What you leave out keeps its value.'],
                ['name' => 'delete-possession-type', 'description' => 'Deletes a possession type with its groups, its fields and every possession of that type, and says how many possessions went.'],
                ['name' => 'import-possession-type', 'description' => 'Creates a new possession type from a file, or lists every problem with it.'],
                ['name' => 'create-possession-field-group', 'description' => 'Adds a named group of fields, placed after the others.'],
                ['name' => 'update-possession-field-group', 'description' => 'Renames a group of fields.'],
                ['name' => 'delete-possession-field-group', 'description' => 'Removes a group; its fields stay, standing alone.'],
                ['name' => 'set-possession-field-group-position', 'description' => 'Moves a group to another place among the groups.'],
                ['name' => 'create-possession-field', 'description' => 'Adds a field, in a group or standing alone, with its kind and choices.'],
                ['name' => 'update-possession-field', 'description' => 'Renames a field, changes its kind or sets its choices. Changing the kind removes the values possessions held for it. What you leave out keeps its value.'],
                ['name' => 'delete-possession-field', 'description' => 'Removes a field.'],
                ['name' => 'set-possession-field-position', 'description' => 'Moves a field to another place among its siblings.'],
            ]],
            ['name' => 'Possessions', 'changesData' => false, 'tools' => [
                ['name' => 'list-possessions', 'description' => 'Lists the things owned recorded in a vault, by name, optionally by type, status or whether they are featured, each with its current value. Compact, it gives only the name, status, type, location and current value of each.'],
                ['name' => 'get-possession', 'description' => 'Returns one possession: its type, its location, its status and the values of its fields.'],
                ['name' => 'list-possession-transactions', 'description' => 'Lists the transactions of a possession, oldest first, with their figures, their total and which one is the acquisition.'],
                ['name' => 'get-possession-transaction', 'description' => 'Returns one transaction of a possession: what happened, when, with whom, its figures, its total and whether it is the acquisition.'],
                ['name' => 'list-possession-insurance-records', 'description' => 'Lists the insurance records of a possession, active first, with the insurer, the insured value, the period and which one is current.'],
                ['name' => 'get-possession-insurance-record', 'description' => 'Returns one insurance record of a possession: the insurer, the status, the insured value, the period and who to call.'],
                ['name' => 'list-possession-maintenance-records', 'description' => 'Lists the maintenance records of a possession, the latest job first, with the conditions before and after and whether the latest one is overdue.'],
                ['name' => 'get-possession-maintenance-record', 'description' => 'Returns one maintenance record of a possession: what was done, by whom, when, for how much and when it is due again.'],
                ['name' => 'list-possession-valuations', 'description' => 'Lists the valuations of a possession, the latest by date first, with the amount, the valuer and which one gives the current value.'],
                ['name' => 'get-possession-valuation', 'description' => 'Returns one valuation of a possession: where the figure comes from, when, how sure it is, who gave it and its documents.'],
                ['name' => 'list-possession-provenance-events', 'description' => 'Lists the provenance events of a possession, oldest first, with how precise each date is and whether it is verified.'],
                ['name' => 'get-possession-provenance-event', 'description' => 'Returns one provenance event of a possession: what happened, when, who it passed from and to, where, and the transaction recording what it cost.'],
                ['name' => 'list-possession-location-stays', 'description' => 'Lists the stays of a possession, oldest first: each location it was kept at, when it arrived and left, and why.'],
                ['name' => 'get-possession-location-stay', 'description' => 'Returns one stay of a possession: the location, when it arrived and left, and whether it is there now.'],
            ]],
            ['name' => 'Possessions, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-possession', 'description' => 'Records something owned in a vault, of one possession type.'],
                ['name' => 'update-possession', 'description' => 'Changes the name, description, location, identifier, status, quantity or featured state of a possession. What you leave out keeps its value.'],
                ['name' => 'delete-possession', 'description' => 'Deletes a possession and the values of its fields.'],
                ['name' => 'set-possession-position', 'description' => 'Moves a featured possession to another place among the featured ones.'],
                ['name' => 'set-possession-values', 'description' => 'Writes the values of the fields of a possession; only the fields named change.'],
                ['name' => 'create-possession-transaction', 'description' => 'Records money or ownership changing hands for a possession: a purchase, a gift, an inheritance, a fee, shipping.'],
                ['name' => 'update-possession-transaction', 'description' => 'Changes a transaction of a possession. What you leave out keeps its value.'],
                ['name' => 'delete-possession-transaction', 'description' => 'Deletes a transaction of a possession with its documents.'],
                ['name' => 'create-possession-insurance-record', 'description' => 'Records one policy period covering a possession: the insurer, the status and the insured value.'],
                ['name' => 'update-possession-insurance-record', 'description' => 'Changes an insurance record of a possession. What you leave out keeps its value.'],
                ['name' => 'delete-possession-insurance-record', 'description' => 'Deletes an insurance record of a possession.'],
                ['name' => 'create-possession-maintenance-record', 'description' => 'Records one job done on a possession: its type, a title and the day it was performed.'],
                ['name' => 'update-possession-maintenance-record', 'description' => 'Changes a maintenance record of a possession. What you leave out keeps its value.'],
                ['name' => 'delete-possession-maintenance-record', 'description' => 'Deletes a maintenance record of a possession.'],
                ['name' => 'create-possession-valuation', 'description' => 'Records what a possession is worth on a date: the type, the date and the amount in its currency. A valuation is never changed afterwards.'],
                ['name' => 'delete-possession-valuation', 'description' => 'Deletes a valuation of a possession and detaches its documents.'],
                ['name' => 'create-possession-provenance-event', 'description' => 'Records one moment in the provenance of a possession: its origin, a change of owner or of custody, or what authenticated it.'],
                ['name' => 'update-possession-provenance-event', 'description' => 'Changes a provenance event of a possession. What you leave out keeps its value.'],
                ['name' => 'delete-possession-provenance-event', 'description' => 'Deletes a provenance event of a possession. The transaction it points at stays.'],
                ['name' => 'move-possession', 'description' => 'Moves a possession to another location on a given day, closing the stay where it is now.'],
                ['name' => 'update-possession-location-stay', 'description' => 'Corrects one stay of a possession. The stays before and after it are not moved.'],
                ['name' => 'delete-possession-location-stay', 'description' => 'Deletes one stay of a possession. Its period then shows as unrecorded.'],
                ['name' => 'list-possession-loans', 'description' => 'Lists the loans of a possession, newest first: who had it or lent it, when, and whether it is back.'],
                ['name' => 'get-possession-loan', 'description' => 'Returns one loan of a possession: the contact, the days, the conditions and the deposit.'],
                ['name' => 'create-possession-loan', 'description' => 'Records a possession lent to or borrowed from a contact, who may be written down with it.'],
                ['name' => 'update-possession-loan', 'description' => 'Changes one loan of a possession, including marking it as returned.'],
                ['name' => 'delete-possession-loan', 'description' => 'Deletes one loan of a possession and the provenance events it wrote.'],
            ]],
            ['name' => 'Possession tags', 'changesData' => false, 'tools' => [
                ['name' => 'list-possession-tags', 'description' => 'Lists the words your account puts on its possessions, sorted by name.'],
                ['name' => 'get-possession-tag', 'description' => 'Returns one possession tag: its name and its colour.'],
            ]],
            ['name' => 'Possession tags, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-possession-tag', 'description' => 'Adds a word to put on possessions, such as insured or to sell.'],
                ['name' => 'update-possession-tag', 'description' => 'Renames a possession tag or changes its colour. What you leave out keeps its value.'],
                ['name' => 'delete-possession-tag', 'description' => 'Deletes a possession tag.'],
            ]],
            ['name' => 'Possession conditions', 'changesData' => false, 'tools' => [
                ['name' => 'list-possession-conditions', 'description' => 'Lists the states your account’s possessions can be in, from best to worst.'],
                ['name' => 'get-possession-condition', 'description' => 'Returns one possession condition: its name, its colour, its icon and its place in the order.'],
            ]],
            ['name' => 'Possession conditions, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-possession-condition', 'description' => 'Adds a state a possession can be in, such as mint or needs repair, at the end of the order.'],
                ['name' => 'update-possession-condition', 'description' => 'Renames a possession condition, or changes its colour or its icon. What you leave out keeps its value.'],
                ['name' => 'set-possession-condition-position', 'description' => 'Moves a possession condition to another place in the order.'],
                ['name' => 'delete-possession-condition', 'description' => 'Deletes a possession condition.'],
            ]],
            ['name' => 'Knowledge', 'changesData' => false, 'tools' => [
                ['name' => 'list-knowledge', 'description' => 'Lists the folders of a vault’s Knowledge in their order, each with the titles of its notes and lists.'],
                ['name' => 'search-knowledge', 'description' => 'Finds the notes and lists holding every word given, in their title, text, description or cells.'],
                ['name' => 'get-knowledge-note', 'description' => 'Returns one note: its title, its folder, its Markdown text and its headings.'],
                ['name' => 'get-knowledge-list', 'description' => 'Returns one list: its description, its columns and its items, each text keyed by its column.'],
            ]],
            ['name' => 'Knowledge, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-knowledge-folder', 'description' => 'Adds a folder to a vault’s Knowledge, after the others.'],
                ['name' => 'update-knowledge-folder', 'description' => 'Renames a folder or moves it among the folders.'],
                ['name' => 'delete-knowledge-folder', 'description' => 'Deletes a folder, moving its notes and lists to another folder or deleting them with it. The last folder stays.'],
                ['name' => 'create-knowledge-note', 'description' => 'Writes a note in Markdown, in a folder.'],
                ['name' => 'update-knowledge-note', 'description' => 'Changes the title, the text or the folder of a note. The text replaces the whole text.'],
                ['name' => 'delete-knowledge-note', 'description' => 'Deletes a note.'],
                ['name' => 'create-knowledge-list', 'description' => 'Starts a list with the columns Name and Note, in a folder.'],
                ['name' => 'update-knowledge-list', 'description' => 'Changes the title, the description or the folder of a list.'],
                ['name' => 'delete-knowledge-list', 'description' => 'Deletes a list with its columns and items.'],
                ['name' => 'create-knowledge-list-column', 'description' => 'Adds a column to a list, after the others.'],
                ['name' => 'update-knowledge-list-column', 'description' => 'Renames a column or moves it among the columns.'],
                ['name' => 'delete-knowledge-list-column', 'description' => 'Removes a column with what every item holds in it. The last column stays.'],
                ['name' => 'create-knowledge-list-item', 'description' => 'Adds an item to a list, its texts keyed by column.'],
                ['name' => 'update-knowledge-list-item', 'description' => 'Changes the texts of an item; only the columns named change.'],
                ['name' => 'delete-knowledge-list-item', 'description' => 'Deletes an item of a list.'],
            ]],
            ['name' => 'Account logs', 'changesData' => false, 'tools' => [
                ['name' => 'list-logs', 'description' => 'Lists what has been done in your account, newest first, a page at a time.'],
                ['name' => 'get-log', 'description' => 'Returns one entry of the activity: who, what, from where and when.'],
                ['name' => 'list-emails-sent', 'description' => 'Lists the emails sent to your account and whether each arrived, a page at a time.'],
                ['name' => 'get-email-sent', 'description' => 'Returns one email, including what it said with links and web addresses removed.'],
            ]],
        ];

        if (! SupportAvailability::isEnabled()) {
            return $groups;
        }

        return [
            ...$groups,
            ['name' => 'Support', 'changesData' => false, 'tools' => [
                ['name' => 'list-support-requests', 'description' => 'Lists what you have asked the people running this instance, the latest activity first.'],
                ['name' => 'get-support-request', 'description' => 'Returns one request with your messages, the replies of the support team and the names of the files sent.'],
            ]],
            ['name' => 'Support, changing', 'changesData' => true, 'tools' => [
                ['name' => 'create-support-request', 'description' => 'Asks the people running this instance for help. A person reads it and replies by email and in the request.'],
                ['name' => 'add-support-message', 'description' => 'Writes in a request already opened, which goes back to the support team. A solved request reopens.'],
                ['name' => 'solve-support-request', 'description' => 'Tells the support team a request needs nothing more.'],
            ]],
            ['name' => 'Support queue, for administrators', 'changesData' => false, 'tools' => [
                ['name' => 'list-support-queue', 'description' => 'Lists the requests of every account, oldest first, with the count in each view. Only with a key of an administrator holding the administration scope.'],
                ['name' => 'get-support-queue-request', 'description' => 'Returns one request of any account with its internal notes, a few facts about the account and its status history.'],
            ]],
            ['name' => 'Support queue, changing', 'changesData' => true, 'tools' => [
                ['name' => 'send-support-reply', 'description' => 'Answers a request: the user is emailed and the request moves to the status you choose.'],
                ['name' => 'add-support-note', 'description' => 'Leaves a note only administrators read. The user is never told it exists.'],
                ['name' => 'change-support-request-status', 'description' => 'Moves a request to another status, written to its history.'],
            ]],
        ];
    }

    /** @return list<string> */
    public function toolsSummary(): array
    {
        $count = array_sum(array_map(fn (array $group): int => count($group['tools']), $this->toolGroups()));

        return [
            "MCP currently exposes {$count} tools. Contact responses include identity and metadata, but not the contents of custom fields or notes.",
            'A life event is something that happened on a date, with the people who were there. Its type decides which custom fields it carries, so read them with get-life-event-type-fields before writing one: values are keyed by field id. The first participant is the person the life event is recorded for. A deleted life event can be put back with restore-life-event.',
            'Our goal is to make every action available in Monica accessible through MCP.',
        ];
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
