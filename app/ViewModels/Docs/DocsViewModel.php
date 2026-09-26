<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

class DocsViewModel
{
    /**
     * @return list<array{icon: string, kind: string, title: string, description: string, url: ?string}>
     */
    public function areas(): array
    {
        return [
            [
                'icon' => 'journal',
                'kind' => __('Guide'),
                'title' => __('Using Monica'),
                'description' => __('What each part of the product is for, from your first contact to reminders, journals and sharing a vault with someone.'),
                'url' => null,
            ],
            [
                'icon' => 'key',
                'kind' => __('Reference'),
                'title' => __('API'),
                'description' => __('Read and write your contacts from your own scripts. Every endpoint, with examples in cURL, JavaScript and PHP.'),
                'url' => route('docs.api.index'),
            ],
            [
                'icon' => 'layers',
                'kind' => __('Reference'),
                'title' => __('MCP'),
                'description' => __('Point an assistant at your instance so it can read your directory and record people in it, with the access you have.'),
                'url' => route('docs.mcp.index'),
            ],
            [
                'icon' => 'device',
                'kind' => __('Reference'),
                'title' => __('CardDAV and CalDAV'),
                'description' => __('Keep the contacts and calendar apps on your phone and computer in step with Monica.'),
                'url' => null,
            ],
        ];
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

        $lines[] = '';
        $lines[] = '## MCP';
        $lines[] = '';
        $lines[] = '- [MCP]('.route('docs.mcp.markdown.index').'): connect an assistant to Monica';

        return implode("\n", $lines)."\n";
    }

    public function host(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
