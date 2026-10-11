<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use App\Helpers\FlagState;
use Illuminate\Support\Facades\Route;

class ApiDocsViewModel
{
    /** @var list<array{name: string, guide: bool, sections: list<ApiDocsSection>}>|null */
    private ?array $groups = null;

    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/api/v1';
    }

    /**
     * The sections a visitor may read: one naming a flag is described only while the flag is on for
     * every account, since the reference is public.
     *
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    public static function offeredToEveryAccount(array $sections): array
    {
        return array_values(array_filter(
            $sections,
            fn (array $section): bool => ! isset($section['flag']) || FlagState::isOn($section['flag'], null),
        ));
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

            return [
                'name' => $group['name'],
                'guide' => $group['guide'] ?? false,
                'sections' => array_map(
                    fn (array $section): ApiDocsSection => new ApiDocsSection($section),
                    self::offeredToEveryAccount($group['sections']),
                ),
            ];
        }, $files);

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

    /**
     * @return array{guides: list<array{id: string, label: string}>, resources: list<array{name: string, items: list<array{id: string, label: string, method: string}>}>}
     */
    public function navigation(): array
    {
        $guides = [];
        $resources = [];

        foreach ($this->groups() as $group) {
            if ($group['guide']) {
                foreach ($group['sections'] as $section) {
                    $guides[] = ['id' => $section->id, 'label' => $section->label];
                }

                continue;
            }

            $resources[] = [
                'name' => $group['name'],
                'items' => array_map(fn (ApiDocsSection $section): array => [
                    'id' => $section->id,
                    'label' => $section->label,
                    'method' => $section->method,
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
