<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

class ApiDocsSection
{
    public readonly string $id;

    public readonly string $title;

    public readonly string $label;

    public readonly string $method;

    public readonly string $path;

    public readonly string $examplePath;

    public readonly bool $auth;

    public readonly string $description;

    /** @var list<string> */
    public readonly array $body;

    public readonly string $paramsTitle;

    /** @var list<array{name: string, type: string, required: bool, description: string, default: string|null, example: mixed}> */
    public readonly array $params;

    /** @var list<array{name: string, type: string, description: string, opaque?: bool}> */
    public readonly array $returnFields;

    public readonly string $rateLimit;

    public readonly ?string $returns;

    public readonly int $responseStatus;

    /** @var array<string, mixed>|null */
    public readonly ?array $response;

    /** @var array<string, mixed>|null */
    private readonly ?array $overriddenBody;

    /** @param  array<string, mixed>  $section */
    public function __construct(array $section)
    {
        $this->id = $section['id'];
        $this->title = $section['title'];
        $this->label = $section['label'] ?? $section['title'];
        $this->method = $section['method'];
        $this->path = $section['path'];
        $this->examplePath = $section['examplePath'] ?? $section['path'];
        $this->auth = $section['auth'] ?? true;
        $this->description = $section['description'];
        $this->body = $section['body'] ?? [];
        $this->rateLimit = $section['rateLimit'];
        $this->returns = $section['returns'] ?? null;
        $this->responseStatus = $section['responseStatus'] ?? 200;
        $this->response = $section['response'] ?? null;
        $this->overriddenBody = $section['exampleBody'] ?? null;
        $this->returnFields = $section['returnFields'] ?? [];

        $this->params = $this->mergeParams($section);
        $this->paramsTitle = $section['paramsTitle'] ?? $this->titleForParams($section);
    }

    public function hasParams(): bool
    {
        return $this->params !== [];
    }

    public function hasReturnFields(): bool
    {
        return $this->returnFields !== [];
    }

    /**
     * @return array<string, array{label: string, lines: list<array{parts: list<array{text: string, colour: string}>}>, code: string}>
     */
    public function samples(): array
    {
        return new ApiDocsCodeSamples($this)->all();
    }

    /** @return list<array{parts: list<array{text: string, colour: string}>}> */
    public function responseLines(): array
    {
        if ($this->response === null) {
            return [];
        }

        return ApiDocsCodeSamples::json($this->response);
    }

    /** @return array<string, mixed> */
    public function exampleBody(): array
    {
        if ($this->overriddenBody !== null) {
            return $this->overriddenBody;
        }

        $body = [];

        foreach ($this->params as $param) {
            if ($param['example'] === null) {
                continue;
            }

            $body[$param['name']] = $param['example'];
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return list<array{name: string, type: string, required: bool, description: string, default: string|null, example: mixed}>
     */
    private function mergeParams(array $section): array
    {
        $params = [];

        foreach (['pathParams', 'queryParams', 'bodyParams'] as $kind) {
            foreach ($section[$kind] ?? [] as $param) {
                $params[] = [
                    'name' => $param['name'],
                    'type' => $param['type'],
                    'required' => $param['required'],
                    'description' => $param['description'],
                    'default' => $param['default'] ?? null,
                    'example' => $param['example'] ?? null,
                ];
            }
        }

        return $params;
    }

    /** @param  array<string, mixed>  $section */
    private function titleForParams(array $section): string
    {
        $kinds = array_filter([
            'Path parameters' => ($section['pathParams'] ?? []) !== [],
            'Query parameters' => ($section['queryParams'] ?? []) !== [],
            'Body parameters' => ($section['bodyParams'] ?? []) !== [],
        ]);

        if (count($kinds) !== 1) {
            return 'Parameters';
        }

        return (string) array_key_first($kinds);
    }
}
