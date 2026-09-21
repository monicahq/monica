<?php

declare(strict_types=1);

return [
    'name' => 'MCP server',
    'guide' => true,
    'sections' => [
        [
            'id' => 'mcp-connecting',
            'title' => 'Connect an assistant',
            'label' => 'Connect an assistant',
            'method' => 'POST',
            'path' => '/mcp',
            'description' => 'Erica speaks the Model Context Protocol, so an assistant can read your directory and record people in it without you exporting anything. Point your assistant at this address and give it an API key.',
            'body' => [
                'The address is your instance followed by /api/mcp. It is the same on a self hosted instance and on the hosted one, and there is nothing to install on your machine beyond the assistant itself.',
                'Authentication is the API key you already hold, sent as a bearer token, exactly as the rest of this reference describes. Make a key for the assistant alone, so that revoking it stops the assistant and nothing else. Choose Assistant as the kind when you make it, and the key list will tell it apart from a personal script.',
                'A key acts as you and reaches exactly what you reach. Every capability below resolves to an endpoint documented here, under that endpoint rules, and the server holds no permission rule of its own.',
            ],
            'rateLimit' => 'Sixty requests per minute, per key, on the hosted version. A self hosted instance applies no limit.',
            'returns' => 'A JSON-RPC answer. A vault or a contact outside your account is reported as not existing rather than as forbidden, in the language you asked for.',
        ],
        [
            'id' => 'mcp-reading',
            'title' => 'What an assistant can read',
            'label' => 'What an assistant can read',
            'method' => 'POST',
            'path' => '/mcp',
            'description' => 'Ten reading capabilities, each answering what the matching endpoint in this reference answers.',
            'body' => [
                'list-vaults gives the compartments your contacts are filed in, and every other capability takes one of their addresses. list-contacts lists the people in one, narrowed by a search, a grouping or an order. get-contact looks one up, and get-me-contact resolves the contact standing for you without your having to know its address first.',
                'list-templates and list-template-versions describe the structures your records are drawn from. list-entity-types, list-relationship-types, list-relationship-categories and list-life-event-categories give the vocabulary your account has shaped for itself.',
                'Reading changes nothing and is not written to your account journal.',
            ],
            'rateLimit' => 'Sixty requests per minute, per key.',
            'returns' => 'The same objects the matching endpoints answer with, so a field documented there is the field you get here.',
        ],
        [
            'id' => 'mcp-writing',
            'title' => 'What an assistant can change',
            'label' => 'What an assistant can change',
            'method' => 'POST',
            'path' => '/mcp',
            'description' => 'Three writing capabilities: create a contact in a vault, correct a contact name, and mark or unmark a contact as a favourite.',
            'body' => [
                'create-contact, update-contact-name and set-contact-favourite each run exactly what the matching endpoint runs, so what is refused there is refused here.',
                'Every change an assistant makes is written to your account journal, recorded as having arrived over MCP and naming the key that made it. That is how you tell a change an assistant made from one you typed yourself. The contact it creates is an ordinary contact, with no marker on it.',
                'Notes, life events and the values written in a contact fields are not offered yet. They arrive here once the API carries them.',
            ],
            'rateLimit' => 'Sixty requests per minute, per key.',
            'returns' => 'The record as it stands after the change, the same object the matching endpoint answers with.',
        ],
    ],
];
