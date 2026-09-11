<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

class ApiDocsFields
{
    /**
     * @param  string  $type  The constant the record's `type` always holds.
     * @param  string  $singular  What one of these is called, in a sentence.
     * @param  list<array{name: string, type: string, description: string, opaque?: bool}>  $attributes
     * @return list<array{name: string, type: string, description: string, opaque?: bool}>
     */
    public static function one(string $type, string $singular, array $attributes, bool $withLinks = true): array
    {
        return [
            ['name' => 'data', 'type' => 'object', 'description' => "The {$singular}."],
            ...self::record($type, 'data', $attributes, $withLinks),
        ];
    }

    /**
     * @param  string  $type  The constant each record's `type` always holds.
     * @param  string  $singular  What one of these is called, in a sentence.
     * @param  string  $plural  What several of them are called, in a sentence.
     * @param  list<array{name: string, type: string, description: string, opaque?: bool}>  $attributes
     * @return list<array{name: string, type: string, description: string, opaque?: bool}>
     */
    public static function page(string $type, string $singular, string $plural, array $attributes, bool $withLinks = true): array
    {
        return [
            ['name' => 'data', 'type' => 'array', 'description' => "The {$plural} on this page. An empty list rather than an error when there are none."],
            ['name' => 'data[]', 'type' => 'object', 'description' => "One {$singular}."],
            ...self::record($type, 'data[]', $attributes, $withLinks),
            ['name' => 'meta', 'type' => 'object', 'description' => 'Where you are in the collection.'],
            ['name' => 'meta.page', 'type' => 'integer', 'description' => 'The page this is, counting from 1.'],
            ['name' => 'meta.per_page', 'type' => 'integer', 'description' => 'How many one page holds, which is the size that was asked for once it has been brought into the allowed range.'],
            ['name' => 'meta.total', 'type' => 'integer', 'description' => "How many {$plural} there are in all, not how many are on this page."],
        ];
    }

    /**
     * The rows for a record that is only ever read inside another one: a page in a
     * version, a field in a section. It carries no links, because there is no
     * address it can be read from on its own.
     *
     * @param  string  $type  The constant the record's `type` always holds.
     * @param  string  $singular  What one of these is called, in a sentence.
     * @param  list<array{name: string, type: string, description: string, opaque?: bool}>  $attributes
     * @return list<array{name: string, type: string, description: string, opaque?: bool}>
     */
    public static function nested(string $type, string $singular, array $attributes): array
    {
        return [
            ['name' => 'data', 'type' => 'object', 'description' => "The {$singular}."],
            ...self::record($type, 'data', $attributes, withLinks: false),
        ];
    }

    /**
     * @param  list<array{name: string, type: string, description: string, opaque?: bool}>  $attributes
     * @return list<array{name: string, type: string, description: string, opaque?: bool}>
     */
    private static function record(string $type, string $prefix, array $attributes, bool $withLinks = true): array
    {
        $rows = [
            ['name' => "{$prefix}.type", 'type' => 'string', 'description' => "Always {$type}. It names the kind of record, so a caller holding several kinds can tell them apart without looking at where they came from."],
            ['name' => "{$prefix}.id", 'type' => 'string', 'description' => 'The identifier of this record, as a string even when it reads as a number. It is what every address naming this record takes.'],
            ['name' => "{$prefix}.attributes", 'type' => 'object', 'description' => 'Everything the record holds.'],
        ];

        foreach ($attributes as $attribute) {
            $rows[] = [...$attribute, 'name' => "{$prefix}.attributes.{$attribute['name']}"];
        }

        if ($withLinks) {
            $rows[] = ['name' => "{$prefix}.links", 'type' => 'object', 'description' => 'Where to find this record.'];
            $rows[] = ['name' => "{$prefix}.links.self", 'type' => 'string', 'description' => 'The absolute address this record is read from.'];
        }

        return $rows;
    }
}
