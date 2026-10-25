<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

/**
 * The API reference written as Markdown, for a reader that is not a browser.
 *
 * The same sections the docs portal draws as HTML, handed over as plain text at
 * `/docs/api.md`, or one endpoint at `/docs/api/{section}.md`, so that a client,
 * an agent or a `curl` can read it without parsing a page.
 */
class ApiDocsMarkdown
{
    private const string BETWEEN_BLOCKS = "\n\n";

    private const string BETWEEN_SECTIONS = "\n\n---\n\n";

    /** @param  list<ApiDocsSection>  $sections */
    public function __construct(
        private readonly array $sections,
    ) {}

    public function render(): string
    {
        $written = array_map(fn (ApiDocsSection $section): string => $this->section($section), $this->sections);

        return trim(implode(self::BETWEEN_SECTIONS, $written))."\n";
    }

    private function section(ApiDocsSection $section): string
    {
        $blocks = [
            '# '.$section->title,
            '`'.$section->method.' /api/v1'.$section->path.'`',
        ];

        if (! $section->auth) {
            $blocks[] = 'No token required.';
        }

        $blocks[] = $section->description;

        foreach ($section->body as $paragraph) {
            $blocks[] = $paragraph;
        }

        foreach ([$this->params($section), $this->returns($section), $this->statusCodes($section)] as $part) {
            if ($part !== []) {
                $blocks = array_merge($blocks, $part);
            }
        }

        $blocks[] = '## Rate limit';
        $blocks[] = $section->rateLimit.' Every answer carries the budget left in `X-RateLimit-Remaining`.';

        $blocks = array_merge($blocks, $this->request($section), $this->response($section));

        return implode(self::BETWEEN_BLOCKS, $blocks);
    }

    /** @return list<string> */
    private function params(ApiDocsSection $section): array
    {
        if (! $section->hasParams()) {
            return [];
        }

        $rows = array_map(function (array $param): string {
            $default = $param['default'] ? ', defaults to '.$param['default'] : '';
            $asked = $param['required'] ? 'required' : 'optional';

            return '- `'.$param['name'].'` ('.$param['type'].', '.$asked.$default.'): '.$param['description'];
        }, $section->params);

        return ['## '.$section->paramsTitle, implode("\n", $rows)];
    }

    /** @return list<string> */
    private function returns(ApiDocsSection $section): array
    {
        if (! $section->returns && ! $section->hasReturnFields()) {
            return [];
        }

        $blocks = ['## Returns'];

        if ($section->returns) {
            $blocks[] = $section->returns;
        }

        if ($section->hasReturnFields()) {
            $rows = array_map(
                fn (array $field): string => '- `'.$field['name'].'` ('.$field['type'].'): '.$field['description'],
                $section->returnFields,
            );

            $blocks[] = implode("\n", $rows);
        }

        return $blocks;
    }

    /** @return list<string> */
    private function statusCodes(ApiDocsSection $section): array
    {
        if (! $section->hasStatusCodes()) {
            return [];
        }

        $rows = array_map(
            fn (array $code): string => '| `'.$code['status'].'` '.$code['name'].' | '.$code['when'].' | '.$code['body'].' |',
            $section->statusCodes,
        );

        return ['## Status codes', implode("\n", ['| Code | When | Body |', '| --- | --- | --- |', ...$rows])];
    }

    /** @return list<string> */
    private function request(ApiDocsSection $section): array
    {
        $blocks = ['## Request'];
        $samples = $section->samples();

        foreach (['curl' => 'sh', 'javascript' => 'javascript', 'php' => 'php'] as $language => $fence) {
            $blocks[] = $this->fenced($fence, $samples[$language]['code']);
        }

        return $blocks;
    }

    /** @return list<string> */
    private function response(ApiDocsSection $section): array
    {
        $lines = $section->responseLines();

        if ($lines === []) {
            return ['## Response', '`'.$section->responseStatus.'`, with no body.'];
        }

        $body = implode("\n", array_map(
            fn (array $line): string => implode('', array_column($line['parts'], 'text')),
            $lines,
        ));

        return ['## Response', '`'.$section->responseStatus.'`', $this->fenced('json', $body)];
    }

    private function fenced(string $language, string $code): string
    {
        return '```'.$language."\n".$code."\n".'```';
    }
}
