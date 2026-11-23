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

    /** @var list<array{status: int, name: string, when: string, body: string}> */
    public readonly array $statusCodes;

    public readonly string $rateLimit;

    public readonly ?string $returns;

    public readonly int $responseStatus;

    public readonly string $responseMediaType;

    public readonly bool $responseIsFile;

    /** @var array<string, mixed>|null */
    public readonly ?array $response;

    public readonly ?ApiDocsSection $querySection;

    /** @var array<string, mixed>|null */
    private readonly ?array $overriddenBody;

    /** @var array<string, mixed> */
    private readonly array $queryExamples;

    /** @var array<string, mixed> */
    private readonly array $bodyExamples;

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
        $this->responseMediaType = $section['responseMediaType'] ?? 'application/json';
        $this->responseIsFile = $section['responseIsFile'] ?? false;
        $this->response = $section['response'] ?? null;
        $this->overriddenBody = $section['exampleBody'] ?? null;
        $this->returnFields = $section['returnFields'] ?? [];
        // One page shows both requests, so it lists the refusal only QUERY can meet.
        $this->statusCodes = $section['querySection']['statusCodes'] ?? $section['statusCodes'] ?? [];
        $this->querySection = isset($section['querySection']) ? new self($section['querySection']) : null;

        $this->queryExamples = self::examples($section['queryParams'] ?? []);
        $this->bodyExamples = self::examples($section['bodyParams'] ?? []);

        $this->params = $this->mergeParams($section);
        $this->paramsTitle = $section['paramsTitle'] ?? $this->titleForParams($section);
    }

    public function answersGetAndQuery(): string
    {
        if (! $this->hasParams()) {
            return 'This endpoint answers GET and QUERY alike. It takes no parameters, so both requests return the same answer.';
        }

        return 'This endpoint answers GET and QUERY alike, and both return the same answer. GET sends the parameters in the address, QUERY in the request body.';
    }

    public function hasParams(): bool
    {
        return $this->params !== [];
    }

    public function hasReturnFields(): bool
    {
        return $this->returnFields !== [];
    }

    public function hasStatusCodes(): bool
    {
        return $this->statusCodes !== [];
    }

    /**
     * Whether the endpoint answers with a JSON body the reference documents field by field. Two
     * hand the caller a file instead, a vCard and the export of an account, and the checks that
     * read an answer field by field have nothing to read in either.
     */
    public function answersWithFields(): bool
    {
        return $this->responseMediaType === 'application/json' && ! $this->responseIsFile;
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
        return $this->overriddenBody ?? $this->bodyExamples;
    }

    /** @return array<string, mixed> */
    public function exampleQuery(): array
    {
        return $this->queryExamples;
    }

    /**
     * The example values a sample sends, taken from one kind of parameter only, so a path or query
     * parameter never lands in a body.
     *
     * @param  list<array<string, mixed>>  $params
     * @return array<string, mixed>
     */
    private static function examples(array $params): array
    {
        $examples = [];

        foreach ($params as $param) {
            if (($param['example'] ?? null) === null) {
                continue;
            }

            $examples[$param['name']] = $param['example'];
        }

        return $examples;
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
