<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use Illuminate\Support\Facades\Route;

/**
 * Everything the API reference at /docs/api shows.
 *
 * It reads the definition files under resources/docs/api, in filename order,
 * and turns each one into a sidebar group. It asks the database nothing: the
 * reference is copy, and the only thing it is checked against is the route
 * table, which the tests do rather than the page.
 *
 * A group marked as a guide renders under Getting started as plain prose with
 * no verb beside it, and its sections are illustrative rather than real
 * endpoints, so they are left out of the comparison against the routes.
 */
class ApiDocsViewModel
{
    /** @var list<array{name: string, guide: bool, sections: list<ApiDocsSection>}>|null */
    private ?array $groups = null;

    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/api/v1';
    }

    /**
     * @return list<array{name: string, guide: bool, sections: list<ApiDocsSection>}>
     */
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
                    $group['sections'],
                ),
            ];
        }, $files);

        return $this->groups;
    }

    /**
     * Every section, in the order the sidebar lists them.
     *
     * @return list<ApiDocsSection>
     */
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
     * The sidebar: the guide pages first, then the resources, each with the
     * verb the entry answers to.
     *
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

    /**
     * The whole reference as plain text, or one section of it.
     *
     * The shaping is a Blade template like every other rendering here, which is
     * what keeps this class a view model rather than a second thing that also
     * writes documents.
     */
    public function markdown(?ApiDocsSection $section = null): string
    {
        $sections = $section === null ? $this->sections() : [$section];

        return trim(view('app.docs.api.markdown', ['sections' => $sections])->render())."\n";
    }

    /**
     * The endpoints the reference claims exist. Guide sections show
     * illustrative requests and are not part of this list.
     *
     * @return list<array{method: string, path: string}>
     */
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

    /**
     * The endpoints that actually exist, written the way a definition file
     * writes them, so the two lists can be compared as they are.
     *
     * @return list<array{method: string, path: string}>
     */
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
