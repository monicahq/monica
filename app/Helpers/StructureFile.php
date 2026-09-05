<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Template;
use App\Models\TemplateVersionSection;

class StructureFile
{
    public const int FORMAT_VERSION = 1;

    /** @return array<string, mixed> */
    public static function ofTemplate(Template $template): array
    {
        $payload = MarketplacePayload::of($template);

        return [
            'monica' => ['format' => 'template', 'format_version' => self::FORMAT_VERSION],
            'exported_at' => now()->toIso8601ZuluString(),
            'template' => $payload['template'],
            'value_lists' => $payload['value_lists'],
        ];
    }

    /** @return array<string, mixed> */
    public static function ofSection(TemplateVersionSection $row): array
    {
        $payload = MarketplacePayload::ofSectionVersion($row->sectionVersion);

        return [
            'monica' => ['format' => 'section', 'format_version' => self::FORMAT_VERSION],
            'exported_at' => now()->toIso8601ZuluString(),
            'from_template' => [
                'name' => $row->templateVersion->template->name,
                'version' => $row->templateVersion->version_number,
            ],
            'section' => $payload['section'],
            'value_lists' => $payload['value_lists'],
        ];
    }

    public static function nameOfTemplate(Template $template): string
    {
        return "{$template->slug}-v{$template->publishedVersion?->version_number}.json";
    }

    public static function nameOfSection(TemplateVersionSection $row): string
    {
        return "{$row->sectionVersion->section->slug}.json";
    }

    /** @param  array<string, mixed>  $file */
    public static function encode(array $file): string
    {
        return json_encode($file, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
