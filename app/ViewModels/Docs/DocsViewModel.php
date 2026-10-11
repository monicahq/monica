<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use App\Helpers\FlagState;
use App\Models\Account;

class DocsViewModel
{
    public function __construct(
        private readonly ?Account $account = null,
    ) {}

    /**
     * @return list<array{icon: string, kind: string, title: string, description: string, url: ?string, hue: int}>
     */
    public function areas(): array
    {
        $areas = [
            [
                'icon' => 'journal',
                'kind' => __('Guide'),
                'title' => __('Using Monica'),
                'description' => __('What each part of the product is for, from your first contact to reminders, journals and sharing a vault with someone.'),
                'url' => route('docs.portal.index', ['locale' => 'en']),
                'hue' => 255,
            ],
            [
                'icon' => 'key',
                'kind' => __('Reference'),
                'title' => __('API'),
                'description' => __('Read and write your contacts from your own scripts. Every endpoint, with examples in cURL, JavaScript and PHP.'),
                'url' => route('docs.api.index'),
                'hue' => 295,
            ],
        ];

        if ($this->mcpIsOn()) {
            $areas[] = [
                'icon' => 'layers',
                'kind' => __('Reference'),
                'title' => __('MCP'),
                'description' => __('Point an assistant at your instance so it can read your directory and record people in it, with the access you have.'),
                'url' => route('docs.mcp.index'),
                'hue' => 190,
            ];
        }

        if ($this->carddavIsOn()) {
            $areas[] = [
                'icon' => 'device',
                'kind' => __('Guide'),
                'title' => __('CardDAV and CalDAV'),
                'description' => __('Keep the contacts and calendar apps on your phone and computer in step with Monica.'),
                'url' => route('docs.carddav.index'),
                'hue' => 20,
            ];
        }

        return $areas;
    }

    public function mcpIsOn(): bool
    {
        return FlagState::isOn('mcp-server', $this->account);
    }

    public function carddavIsOn(): bool
    {
        return FlagState::isOn('carddav', $this->account);
    }

    public function llmsText(): string
    {
        $lines = [
            '# Monica',
            '',
            '> Monica is an open source personal relationship manager. This file lists its documentation as Markdown.',
            '',
            '## API reference',
            '',
            '- [Complete API reference]('.route('docs.api.markdown.index').'): every endpoint in one file',
        ];

        foreach (new ApiDocsViewModel()->sections() as $section) {
            $lines[] = "- [{$section->title}](".route('docs.api.markdown.show', ['section' => $section->id]).')';
        }

        if ($this->mcpIsOn()) {
            $lines[] = '';
            $lines[] = '## MCP';
            $lines[] = '';
            $lines[] = '- [MCP]('.route('docs.mcp.markdown.index').'): connect an assistant to Monica';
        }

        if ($this->carddavIsOn()) {
            $lines[] = '';
            $lines[] = '## CardDAV and CalDAV';
            $lines[] = '';
            $lines[] = '- [Sync your contacts and calendars]('.route('docs.carddav.markdown.index').'): connect a device, understand what syncs, and manage access';
        }

        return implode("\n", $lines)."\n";
    }

    public function registrationIsEnabled(): bool
    {
        return (bool) config('monica.registration_enabled');
    }

    public function host(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
