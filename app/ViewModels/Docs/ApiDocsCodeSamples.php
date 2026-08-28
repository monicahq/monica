<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

/**
 * The ready to run requests shown beside one endpoint, in the three languages
 * somebody is most likely to reach for first.
 *
 * A sample is a list of lines, and a line is a list of coloured parts, because
 * the page has no syntax highlighter in it: the colouring is decided here and
 * the template only prints spans. What gets coloured is quoted text, which is
 * enough to make a request legible without pretending to parse three languages.
 */
class ApiDocsCodeSamples
{
    private const LABELS = [
        'curl' => 'cURL',
        'javascript' => 'JavaScript',
        'php' => 'PHP',
    ];

    public function __construct(
        private readonly ApiDocsSection $section,
    ) {}

    /**
     * @return array<string, array{label: string, lines: list<array{parts: list<array{text: string, colour: string}>}>, code: string}>
     */
    public function all(): array
    {
        $samples = [];

        foreach (self::LABELS as $key => $label) {
            $lines = match ($key) {
                'curl' => $this->curl(),
                'javascript' => $this->javascript(),
                default => $this->php(),
            };

            $samples[$key] = [
                'label' => $label,
                'lines' => array_map(self::highlight(...), $lines),
                'code' => implode("\n", $lines),
            ];
        }

        return $samples;
    }

    /**
     * Split one line into coloured parts. Anything inside quotes is the value
     * somebody is going to change, so that is what carries the colour.
     *
     * @return array{parts: list<array{text: string, colour: string}>}
     */
    public static function highlight(string $line): array
    {
        $parts = [];
        $offset = 0;

        preg_match_all('/"[^"]*"|\'[^\']*\'/', $line, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$text, $position]) {
            if ($position > $offset) {
                $parts[] = ['text' => substr($line, $offset, $position - $offset), 'colour' => 'ink'];
            }

            $parts[] = ['text' => $text, 'colour' => 'brand'];
            $offset = $position + strlen($text);
        }

        if ($offset < strlen($line)) {
            $parts[] = ['text' => substr($line, $offset), 'colour' => 'ink'];
        }

        if ($parts === []) {
            $parts[] = ['text' => ' ', 'colour' => 'ink'];
        }

        return ['parts' => $parts];
    }

    /**
     * Pretty print a response body and colour it the same way as the samples.
     *
     * @param  array<string, mixed>  $response
     * @return list<array{parts: list<array{text: string, colour: string}>}>
     */
    public static function json(array $response): array
    {
        $encoded = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return array_map(self::highlight(...), explode("\n", (string) $encoded));
    }

    /**
     * @return list<string>
     */
    private function curl(): array
    {
        $lines = ['curl '.$this->url().' \\'];

        if ($this->section->method !== 'GET') {
            $lines[] = '  -X '.$this->section->method.' \\';
        }

        if ($this->section->auth) {
            $lines[] = '  -H "Authorization: Bearer $MONICA_TOKEN" \\';
        }

        $body = $this->section->exampleBody();

        if ($body === []) {
            $lines[count($lines) - 1] = rtrim(end($lines), ' \\');

            return $lines;
        }

        $lines[] = '  -H "Content-Type: application/json" \\';
        $encoded = explode("\n", (string) json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $lines[] = "  -d '".array_shift($encoded);

        foreach ($encoded as $index => $line) {
            $lines[] = '  '.$line.($index === count($encoded) - 1 ? "'" : '');
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function javascript(): array
    {
        $options = [];
        $body = $this->section->exampleBody();

        if ($this->section->method !== 'GET') {
            $options[] = "  method: '".$this->section->method."',";
        }

        $headers = [];

        if ($this->section->auth) {
            $headers[] = 'Authorization: `Bearer ${token}`';
        }

        if ($body !== []) {
            $headers[] = "'Content-Type': 'application/json'";
        }

        if ($headers !== []) {
            $options[] = '  headers: { '.implode(', ', $headers).' },';
        }

        if ($body !== []) {
            $options[] = '  body: JSON.stringify({';

            foreach ($body as $name => $value) {
                $options[] = '    '.$name.': '.$this->javascriptValue($value).',';
            }

            $options[] = '  }),';
        }

        // Nothing comes back from a 204, so a sample that read a body would
        // fail for anybody who copied it.
        $read = $this->section->responseStatus === 204
            ? []
            : ['const data = await response.json()'];

        if ($options === []) {
            return [
                "const response = await fetch('".$this->url()."')",
                ...$read,
            ];
        }

        return [
            "const response = await fetch('".$this->url()."', {",
            ...$options,
            '})',
            ...$read,
        ];
    }

    /**
     * @return list<string>
     */
    private function php(): array
    {
        $call = mb_strtolower($this->section->method);
        $client = $this->section->auth ? 'Http::withToken($token)->'.$call : 'Http::'.$call;
        $body = $this->section->exampleBody();

        if ($body === []) {
            return ['$response = '.$client."('".$this->url()."');"];
        }

        $lines = ['$response = '.$client."('".$this->url()."', ["];

        foreach ($body as $name => $value) {
            $lines[] = "    '".$name."' => ".$this->phpValue($value).',';
        }

        $lines[] = ']);';

        return $lines;
    }

    private function javascriptValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".$value."'";
    }

    private function phpValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".$value."'";
    }

    private function url(): string
    {
        return ApiDocsViewModel::baseUrl().$this->section->examplePath;
    }
}
