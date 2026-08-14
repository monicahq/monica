<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Template;
use App\Models\TemplateVersionSection;

/**
 * The file somebody gets when they export a template or a section.
 *
 * It is the marketplace payload with a wrapper around it saying what the file
 * is and when it was written. The wrapper is the whole point of having a format
 * of its own: a file that has been sitting in somebody's downloads folder for a
 * year has to be readable by whatever the product has become, and the only way
 * to know how to read it is for the file to say what it is.
 *
 * What is written down is the frozen version and never the live structure, for
 * the same reason the catalogue reads it that way. A file that quietly followed
 * the state of somebody's account would describe something that has since
 * changed.
 *
 * Nothing anybody wrote into a record is here. The file describes the form and
 * never the answers, and the labels are the only words in it somebody typed.
 */
class StructureFile
{
    /**
     * Which reading of the format this is. It travels in the file so that
     * whatever reads it later can tell whether it knows how.
     */
    public const int FORMAT_VERSION = 1;

    /**
     * Write down a whole template: its pages, the sections on them, the fields
     * inside those, and the lists of choices they point at.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Write down one section of a template, on its own.
     *
     * The version written down is the one the template pins rather than
     * whatever that section has published since, so the file says what the
     * screen it was exported from says. Where it came from is written down
     * beside it: a section read on its own a year later is a good deal easier
     * to place when the file names the template it was cut out of.
     *
     * @return array<string, mixed>
     */
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

    /**
     * What the file is called once it is on somebody's computer. The slug is
     * already the readable, unique name of the thing within the account, and
     * the version is there because two exports of the same template a month
     * apart are two different files.
     */
    public static function nameOfTemplate(Template $template): string
    {
        return "{$template->slug}-v{$template->publishedVersion?->version_number}.json";
    }

    /**
     * What a section file is called. There is no version in it: a section is
     * exported from the template that carries it, and the version somebody
     * would recognise is that template's rather than the section's own.
     */
    public static function nameOfSection(TemplateVersionSection $row): string
    {
        return "{$row->sectionVersion->section->slug}.json";
    }

    /**
     * The file as text, laid out to be read. Somebody is asked to look at what
     * is about to leave their account, so the bytes they download and the bytes
     * on the screen have to be the same bytes, formatting included.
     *
     * @param  array<string, mixed>  $file
     */
    public static function encode(array $file): string
    {
        return json_encode($file, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
