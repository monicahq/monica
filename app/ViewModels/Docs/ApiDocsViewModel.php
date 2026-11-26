<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use App\Helpers\ChangelogAvailability;
use App\Helpers\FlagState;
use App\Helpers\SupportAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;

class ApiDocsViewModel
{
    public const string LAST_GENERATED = '2026-11-26';

    /** @var list<array{name: string, guide: bool, sections: list<ApiDocsSection>}>|null */
    private ?array $groups = null;

    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/api/v1';
    }

    /**
     * The sections a visitor may read: one naming a flag, or several, is described only while every
     * flag it names is on for every account, since the reference is public. One marked as support is
     * described only while support is switched on for the instance, and one marked as changelog only
     * while What's new is.
     *
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    public static function offeredToEveryAccount(array $sections): array
    {
        return array_values(array_filter(
            $sections,
            fn (array $section): bool => (! ($section['support'] ?? false) || SupportAvailability::isEnabled())
                && (! ($section['changelog'] ?? false) || ChangelogAvailability::isEnabled())
                && collect((array) ($section['flag'] ?? []))->every(fn (string $flag): bool => FlagState::isOn($flag, null)),
        ));
    }

    /**
     * Gives every GET section the section of its QUERY request, derived from it rather than written,
     * so the two cannot drift and share one page. The parameters GET reads from the address travel in
     * the body.
     *
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    public static function withQuerySections(array $sections): array
    {
        $withQuerySections = [];

        foreach ($sections as $section) {
            if ($section['method'] !== 'GET') {
                $withQuerySections[] = $section;

                continue;
            }

            $bodyParams = $section['queryParams'] ?? [];
            $statusCodes = $section['statusCodes'] ?? [];

            if ($bodyParams !== []) {
                $statusCodes = self::withRefusalOfParameterSentTwice($statusCodes);
            }

            $querySection = [
                ...$section,
                'id' => "{$section['id']}-query",
                'method' => 'QUERY',
                'title' => "{$section['title']} with QUERY",
                'label' => $section['label'] ?? $section['title'],
                'queryParams' => [],
                'bodyParams' => $bodyParams,
                'statusCodes' => $statusCodes,
            ];

            $withQuerySections[] = [...$section, 'querySection' => $querySection];
        }

        return $withQuerySections;
    }

    /**
     * The table of status codes holds one row per status, so the refusal joins an existing 422 row
     * rather than adding a second one.
     *
     * @param  list<array{status: int, name: string, when: string, body: string}>  $statusCodes
     * @return list<array{status: int, name: string, when: string, body: string}>
     */
    private static function withRefusalOfParameterSentTwice(array $statusCodes): array
    {
        foreach ($statusCodes as $index => $statusCode) {
            if ($statusCode['status'] === 422) {
                $statusCodes[$index]['when'] = "{$statusCode['when']} Or, with QUERY, a parameter was sent both in the address and in the body.";

                return $statusCodes;
            }
        }

        $statusCodes[] = [
            'status' => 422,
            'name' => 'Unprocessable Content',
            'when' => 'With QUERY, a parameter was sent both in the address and in the body.',
            'body' => 'A message, and an errors object naming each parameter sent twice.',
        ];

        return $statusCodes;
    }

    /** @return list<array{name: string, guide: bool, sections: list<ApiDocsSection>}> */
    public function groups(): array
    {
        if ($this->groups !== null) {
            return $this->groups;
        }

        $files = glob(resource_path('docs/api').'/*.php') ?: [];
        sort($files);

        $this->groups = array_map(function (string $file): array {
            $group = require $file;
            $guide = $group['guide'] ?? false;
            $sections = self::offeredToEveryAccount($group['sections']);

            return [
                'name' => $group['name'],
                'guide' => $guide,
                'sections' => array_map(
                    fn (array $section): ApiDocsSection => new ApiDocsSection($section),
                    $guide ? $sections : self::withQuerySections($sections),
                ),
            ];
        }, $files);

        $this->groups = array_values(array_filter($this->groups, fn (array $group): bool => $group['sections'] !== []));

        return $this->groups;
    }

    /** @return list<ApiDocsSection> */
    public function sections(): array
    {
        $sections = [];

        foreach ($this->groups() as $group) {
            foreach ($group['sections'] as $section) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    public function section(string $id): ?ApiDocsSection
    {
        foreach ($this->sections() as $section) {
            if ($section->id === $id) {
                return $section;
            }
        }

        return null;
    }

    public function sectionFor(string $method, string $path): ?ApiDocsSection
    {
        foreach ($this->groups() as $group) {
            if ($group['guide']) {
                continue;
            }

            foreach ($group['sections'] as $section) {
                if ($section->method === $method && $section->path === $path) {
                    return $section;
                }

                if ($section->querySection?->method === $method && $section->path === $path) {
                    return $section;
                }
            }
        }

        return null;
    }

    public function previous(ApiDocsSection $section): ?ApiDocsSection
    {
        $sections = $this->sections();

        foreach ($sections as $index => $candidate) {
            if ($candidate->id === $section->id) {
                return $sections[$index - 1] ?? null;
            }
        }

        return null;
    }

    public function next(ApiDocsSection $section): ?ApiDocsSection
    {
        $sections = $this->sections();

        foreach ($sections as $index => $candidate) {
            if ($candidate->id === $section->id) {
                return $sections[$index + 1] ?? null;
            }
        }

        return null;
    }

    public function lastGenerated(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::LAST_GENERATED);
    }

    public function supportUrl(): ?string
    {
        if (! SupportAvailability::isOffered()) {
            return null;
        }

        return route('support.requests.new');
    }

    /**
     * @return array{guides: list<array{id: string, label: string, url: string}>, resources: list<array{name: string, items: list<array{id: string, label: string, methods: list<string>}>}>}
     */
    public function navigation(): array
    {
        $guides = [];
        $resources = [];

        foreach ($this->groups() as $group) {
            if ($group['guide']) {
                foreach ($group['sections'] as $section) {
                    $guides[] = ['id' => $section->id, 'label' => $section->label, 'url' => route('docs.api.show', ['section' => $section->id])];
                }

                $guides[] = ['id' => 'changelog', 'label' => 'Changelog', 'url' => route('docs.api.changelog.index')];

                continue;
            }

            $resources[] = [
                'name' => $group['name'],
                'items' => array_map(fn (ApiDocsSection $section): array => [
                    'id' => $section->id,
                    'label' => $section->label,
                    'methods' => $section->querySection === null ? [$section->method] : [$section->method, $section->querySection->method],
                ], $group['sections']),
            ];
        }

        return ['guides' => $guides, 'resources' => $resources];
    }

    public function markdown(?ApiDocsSection $section = null): string
    {
        $sections = $section === null ? $this->sections() : [$section];

        return new ApiDocsMarkdown($sections)->render();
    }

    /** @return list<array{method: string, path: string}> */
    public function documentedRoutes(): array
    {
        $routes = [];

        foreach ($this->groups() as $group) {
            if ($group['guide']) {
                continue;
            }

            foreach ($group['sections'] as $section) {
                $routes[] = ['method' => $section->method, 'path' => $section->path];

                if ($section->querySection !== null) {
                    $routes[] = ['method' => $section->querySection->method, 'path' => $section->path];
                }
            }
        }

        return $routes;
    }

    /** @return list<array{method: string, path: string}> */
    public function registeredRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $routes[] = [
                    'method' => $method,
                    'path' => '/'.mb_substr($route->uri(), mb_strlen('api/v1/')),
                ];
            }
        }

        return $routes;
    }
}
