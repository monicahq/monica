<?php

declare(strict_types=1);

namespace App\Actions;

use App\ViewModels\Docs\ApiDocsViewModel;
use Illuminate\Support\Facades\File;

/**
 * Describe the JSON API as an OpenAPI document, built from the same reference pages the docs portal
 * renders, so a person reading prose and a machine reading a schema are told the same thing.
 *
 * Nothing is stored. The document is assembled from `resources/docs/api` whenever it is asked for,
 * so it cannot fall behind the reference it comes from.
 */
class BuildOpenApiDocument
{
    /** @return array<string, mixed> */
    public function execute(): array
    {
        $groups = $this->groups();

        return [
            'openapi' => '3.2.0',
            'info' => $this->info(),
            'servers' => [
                [
                    'url' => ApiDocsViewModel::baseUrl(),
                    'description' => 'This instance.',
                ],
            ],
            'tags' => $this->tags($groups),
            'security' => [['bearerAuth' => []]],
            'paths' => $this->paths($groups),
            'components' => $this->components(),
        ];
    }

    /**
     * The reference files, guides left out. A guide borrows a real endpoint's method and path so its
     * page can show a code sample, so counting one would describe that endpoint several times over.
     *
     * @return list<array{name: string, description: string, sections: list<array<string, mixed>>}>
     */
    private function groups(): array
    {
        $files = File::files(resource_path('docs/api'));
        sort($files);

        $groups = [];

        foreach ($files as $file) {
            $group = File::getRequire((string) $file);

            if ($group['guide'] ?? false) {
                continue;
            }

            $sections = ApiDocsViewModel::offeredToEveryAccount($group['sections']);

            if ($sections === []) {
                continue;
            }

            $groups[] = [
                'name' => $group['name'],
                'description' => $group['description'] ?? '',
                'sections' => $sections,
            ];
        }

        return $groups;
    }

    /** @return array<string, mixed> */
    private function info(): array
    {
        $name = (string) config('app.name');

        return [
            'title' => $name.' API',
            'description' => 'Everything this product does in its own screens can be done here. Records are answered as a type, an id, an attributes object and a link to themselves, timestamps are a count of seconds since 1970, and anything belonging to another account answers 404 rather than saying it exists.',
            'version' => 'v1',
            'license' => [
                'name' => 'AGPL-3.0-or-later',
                'identifier' => 'AGPL-3.0-or-later',
            ],
            'contact' => [
                'name' => $name,
                'url' => rtrim((string) config('app.url'), '/').'/docs/api',
            ],
        ];
    }

    /**
     * @param  list<array{name: string, description: string, sections: list<array<string, mixed>>}>  $groups
     * @return list<array<string, string>>
     */
    private function tags(array $groups): array
    {
        return array_map(fn (array $group): array => [
            'name' => $group['name'],
            'description' => $group['description'],
        ], $groups);
    }

    /**
     * @param  list<array{name: string, description: string, sections: list<array<string, mixed>>}>  $groups
     * @return array<string, mixed>
     */
    private function paths(array $groups): array
    {
        $paths = [];

        foreach ($groups as $group) {
            foreach ($group['sections'] as $section) {
                $paths[$section['path']][mb_strtolower($section['method'])] = $this->operation($section, $group['name']);
            }
        }

        return $paths;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function operation(array $section, string $group): array
    {
        $operation = [
            'operationId' => $section['id'],
            'tags' => [$group],
            'summary' => $section['title'],
            'description' => $this->prose($section),
        ];

        $parameters = $this->parameters($section);

        if ($parameters !== []) {
            $operation['parameters'] = $parameters;
        }

        $body = $this->requestBody($section);

        if ($body !== null) {
            $operation['requestBody'] = $body;
        }

        $operation['responses'] = $this->responses($section);

        if (($section['auth'] ?? true) === false) {
            $operation['security'] = [];
        }

        return $operation;
    }

    /** @param array<string, mixed> $section */
    private function prose(array $section): string
    {
        $paragraphs = array_merge([$section['description']], $section['body'] ?? []);
        $paragraphs[] = 'Rate limit: '.$section['rateLimit'];

        return implode("\n\n", $paragraphs);
    }

    /**
     * @param  array<string, mixed>  $section
     * @return list<array<string, mixed>>
     */
    private function parameters(array $section): array
    {
        $parameters = [];

        foreach ($section['pathParams'] ?? [] as $parameter) {
            $parameters[] = $this->parameter($parameter, 'path', true);
        }

        foreach ($section['queryParams'] ?? [] as $parameter) {
            $parameters[] = $this->parameter($parameter, 'query', $parameter['required']);
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $parameter
     * @return array<string, mixed>
     */
    private function parameter(array $parameter, string $in, bool $required): array
    {
        $described = [
            'name' => $parameter['name'],
            'in' => $in,
            'required' => $required,
            'description' => $parameter['description'],
            'schema' => $this->schemaForParameter($parameter),
        ];

        if (($parameter['example'] ?? null) !== null) {
            $described['example'] = $this->castExample($parameter['example'], $parameter['type']);
        }

        return $described;
    }

    /**
     * @param  array<string, mixed>  $parameter
     * @return array<string, mixed>
     */
    private function schemaForParameter(array $parameter): array
    {
        $schema = $this->schemaForType($parameter['type']);

        if (($parameter['default'] ?? null) !== null) {
            $schema['default'] = $this->castToType((string) $parameter['default'], $parameter['type']);
        }

        return $schema;
    }

    /**
     * The reference holds a default and an example as strings even where the parameter is a number,
     * because they are written to be read. A generated client wants them as the thing they are.
     */
    private function castToType(string $value, string $type): string|int|bool
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => $value,
        };
    }

    /** A worked example the reference wrote as anything but a string is already what it should be. */
    private function castExample(mixed $value, string $type): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return $this->castToType($value, $type);
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>|null
     */
    private function requestBody(array $section): ?array
    {
        $parameters = $section['bodyParams'] ?? [];

        if ($parameters === []) {
            return null;
        }

        $schema = $this->fold(array_map(fn (array $parameter): array => [
            'name' => $parameter['name'],
            'required' => $parameter['required'],
            'schema' => $this->schemaForParameter($parameter) + ['description' => $parameter['description']],
        ], $parameters));

        $mediaType = $this->mediaType($parameters);
        $content = [$mediaType => ['schema' => $schema]];
        $example = $this->exampleBody($parameters);

        if ($example !== []) {
            $content[$mediaType]['example'] = $example;
        }

        return [
            'required' => ($schema['required'] ?? []) !== [],
            'content' => $content,
        ];
    }

    /**
     * A parameter the reference gave no example for is left out, which is what the portal already
     * shows a reader.
     *
     * @param  list<array<string, mixed>>  $parameters
     * @return array<string, mixed>
     */
    private function exampleBody(array $parameters): array
    {
        $example = [];

        foreach ($parameters as $parameter) {
            $written = $parameter['example'] ?? null;

            if ($written === null) {
                if ($parameter['required']) {
                    return [];
                }

                continue;
            }

            if (str_contains($parameter['name'], '.')) {
                return [];
            }

            $example[$parameter['name']] = $this->castExample($written, $parameter['type']);
        }

        return $example;
    }

    /** @param list<array<string, mixed>> $parameters */
    private function mediaType(array $parameters): string
    {
        foreach ($parameters as $parameter) {
            if ($parameter['type'] === 'file') {
                return 'multipart/form-data';
            }
        }

        return 'application/json';
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<int, mixed>
     */
    private function responses(array $section): array
    {
        $status = $section['responseStatus'] ?? 200;

        return [$status => $this->answer($section, $status)] + $this->refusals($section);
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function answer(array $section, int $status): array
    {
        $answer = ['description' => $section['returns'] ?? 'The request succeeded.'];

        if ($status === 204) {
            return $answer;
        }

        $mediaType = $section['responseMediaType'] ?? 'application/json';

        if ($mediaType !== 'application/json') {
            $answer['content'] = [$mediaType => ['schema' => ['type' => 'string']]];

            return $answer;
        }

        $content = ['schema' => $this->schemaFromFields($section['returnFields'] ?? [])];

        if (($section['response'] ?? null) !== null) {
            $content['example'] = $section['response'];
        }

        $answer['content'] = [$mediaType => $content];

        return $answer;
    }

    /**
     * Derived rather than declared, because what a caller can be refused follows from what the
     * endpoint already says about itself.
     *
     * @param  array<string, mixed>  $section
     * @return array<int, mixed>
     */
    private function refusals(array $section): array
    {
        $refusals = [];

        if (($section['auth'] ?? true) === true) {
            $refusals[401] = $this->refusal('No token was sent, or the one sent has been revoked.');
        }

        if (($section['pathParams'] ?? []) !== []) {
            $refusals[404] = $this->refusal('Nothing answers to that. A record held by another account answers the same way, so that nothing is disclosed about it.');
        }

        if (($section['bodyParams'] ?? []) !== []) {
            $refusals[422] = $this->invalid();
        }

        $refusals[429] = $this->refusal('The rate limit was reached. Retry-After says how long to wait.');

        return $refusals;
    }

    /** @return array<string, mixed> */
    private function refusal(string $description): array
    {
        return $this->pointingAt('Error', $description);
    }

    /** @return array<string, mixed> */
    private function invalid(): array
    {
        return $this->pointingAt(
            'ValidationError',
            'The request was refused, with every field at fault named at once.',
        );
    }

    /** @return array<string, mixed> */
    private function pointingAt(string $schema, string $description): array
    {
        return [
            'description' => $description,
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/'.$schema],
                ],
            ],
        ];
    }

    /**
     * The reference names a returned field by its whole dotted path, which is what a reader wants to
     * see. A schema wants the nesting back, so it is folded here.
     *
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function schemaFromFields(array $fields): array
    {
        $schema = $this->fold(array_map(fn (array $field): array => [
            'name' => $field['name'],
            'required' => false,
            'schema' => $this->schemaForField($field),
        ], $fields));

        if (isset($schema['properties']['meta'])) {
            $schema['properties']['meta'] = ['$ref' => '#/components/schemas/PaginationMeta'];
        }

        return $schema;
    }

    /**
     * @param  list<array{name: string, required: bool, schema: array<string, mixed>}>  $entries
     * @return array<string, mixed>
     */
    private function fold(array $entries): array
    {
        $tree = [];

        foreach ($entries as $entry) {
            $node = &$tree;

            foreach ($this->segments($entry['name']) as $segment) {
                $node = &$node['properties'][$segment['key']];

                if ($segment['item']) {
                    $node = &$node['items'];
                }
            }

            $node['schema'] = $entry['schema'];
            $node['required'] = $entry['required'];
            unset($node);
        }

        return $this->render($tree);
    }

    /** @return list<array{key: string, item: bool}> */
    private function segments(string $name): array
    {
        $segments = [];

        foreach (explode('.', $name) as $part) {
            $item = str_ends_with($part, '[]');

            $segments[] = [
                'key' => $item ? mb_substr($part, 0, -2) : $part,
                'item' => $item,
            ];
        }

        return $segments;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function render(array $node): array
    {
        $schema = $node['schema'] ?? ['type' => 'object'];

        if (isset($node['items'])) {
            $schema['items'] = $this->render($node['items']);
        }

        if (isset($node['properties'])) {
            $schema['properties'] = array_map(
                fn (array $child): array => $this->render($child),
                $node['properties'],
            );

            $required = array_keys(array_filter(
                $node['properties'],
                fn (array $child): bool => $child['required'] ?? false,
            ));

            if ($required !== []) {
                $schema['required'] = $required;
            }
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function schemaForField(array $field): array
    {
        if ($field['opaque'] ?? false) {
            return $this->orNull([
                'type' => 'object',
                'additionalProperties' => true,
                'description' => $field['description'],
            ]);
        }

        return $this->orNull($this->schemaForType($field['type']) + ['description' => $field['description']]);
    }

    /**
     * The reference records what a field is when it holds something, and says nothing about whether
     * it may hold nothing: the check that holds those types to real answers passes over a null
     * rather than judging it. So a returned value is described as admitting null, because claiming
     * otherwise would put in the document a promise the reference never made.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function orNull(array $schema): array
    {
        $schema['type'] = [$schema['type'], 'null'];

        return $schema;
    }

    /** @return array<string, mixed> */
    private function schemaForType(string $type): array
    {
        return match ($type) {
            'integer', 'timestamp' => ['type' => 'integer'],
            'boolean' => ['type' => 'boolean'],
            'object' => ['type' => 'object'],
            'array' => ['type' => 'array'],
            'uuid' => ['type' => 'string', 'format' => 'uuid'],
            'file' => ['type' => 'string', 'format' => 'binary'],
            default => ['type' => 'string'],
        };
    }

    /** @return array<string, mixed> */
    private function components(): array
    {
        $counts = [
            'type' => 'integer',
            'description' => 'How many there are, and where in them this page sits.',
        ];

        return [
            'schemas' => [
                'Error' => [
                    'type' => 'object',
                    'properties' => ['message' => ['type' => 'string']],
                    'required' => ['message'],
                ],
                'ValidationError' => [
                    'type' => 'object',
                    'properties' => [
                        'message' => ['type' => 'string'],
                        'errors' => [
                            'type' => 'object',
                            'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                    'required' => ['message', 'errors'],
                ],
                'PaginationMeta' => [
                    'type' => 'object',
                    'properties' => [
                        'page' => $counts,
                        'per_page' => $counts,
                        'total' => $counts,
                    ],
                    'required' => ['page', 'per_page', 'total'],
                ],
            ],
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'description' => 'A personal access token, sent as `Authorization: Bearer <token>`. Create one in Settings.',
                ],
            ],
        ];
    }
}
