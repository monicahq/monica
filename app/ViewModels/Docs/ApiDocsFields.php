<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

/**
 * The envelope every record in this API arrives in, described once.
 *
 * Sixteen endpoints answer with a record or a page of them, and all of them wrap
 * it the same way. Written out by hand in each reference file that would be
 * sixteen slightly different accounts of one `type`, one `id`, one `links.self`
 * and one paging block, which is exactly what the reference is not allowed to
 * be: two fields of the same kind have to read the same way wherever a reader
 * meets them. Written here it cannot drift, and the group files are left saying
 * only the part that actually differs, which is the attributes.
 *
 * A field is named by its whole path rather than by a leaf name and a depth
 * number. The path says which parent a field belongs to in every rendering,
 * including the plain text one, which has no indentation to lean on and is what
 * a program and a screen reader are handed. It also means the indentation on the
 * page is computed from the name, so the two can never disagree.
 */
class ApiDocsFields
{
    /**
     * One record, as an answer that carries a single one describes it.
     *
     * @param  string  $type  The constant the record's `type` always holds.
     * @param  string  $singular  What one of these is called, in a sentence.
     * @param  list<array{name: string, type: string, description: string, opaque?: bool}>  $attributes
     * @return list<array{name: string, type: string, description: string, opaque?: bool}>
     */
    public static function one(string $type, string $singular, array $attributes): array
    {
        return [
            ['name' => 'data', 'type' => 'object', 'description' => "The {$singular}."],
            ...self::record($type, 'data', $attributes),
        ];
    }

    /**
     * A page of records, with the counters needed to walk the rest.
     *
     * @param  string  $type  The constant each record's `type` always holds.
     * @param  string  $singular  What one of these is called, in a sentence.
     * @param  string  $plural  What several of them are called, in a sentence.
     * @param  list<array{name: string, type: string, description: string, opaque?: bool}>  $attributes
     * @return list<array{name: string, type: string, description: string, opaque?: bool}>
     */
    public static function page(string $type, string $singular, string $plural, array $attributes): array
    {
        return [
            ['name' => 'data', 'type' => 'array', 'description' => "The {$plural} on this page. An empty list rather than an error when there are none."],
            ['name' => 'data[]', 'type' => 'object', 'description' => "One {$singular}."],
            ...self::record($type, 'data[]', $attributes),
            ['name' => 'meta', 'type' => 'object', 'description' => 'Where you are in the collection.'],
            ['name' => 'meta.page', 'type' => 'integer', 'description' => 'The page this is, counting from 1.'],
            ['name' => 'meta.per_page', 'type' => 'integer', 'description' => 'How many one page holds, which is the size that was asked for once it has been brought into the allowed range.'],
            ['name' => 'meta.total', 'type' => 'integer', 'description' => "How many {$plural} there are in all, not how many are on this page."],
        ];
    }

    /**
     * The parts of a record that are the same whatever kind of record it is,
     * with the attributes dropped in the middle of them.
     *
     * @param  list<array{name: string, type: string, description: string, opaque?: bool}>  $attributes
     * @return list<array{name: string, type: string, description: string, opaque?: bool}>
     */
    private static function record(string $type, string $prefix, array $attributes): array
    {
        $rows = [
            ['name' => "{$prefix}.type", 'type' => 'string', 'description' => "Always {$type}. It names the kind of record, so a caller holding several kinds can tell them apart without looking at where they came from."],
            ['name' => "{$prefix}.id", 'type' => 'string', 'description' => 'The identifier of this record, as a string even when it reads as a number. It is what every address naming this record takes.'],
            ['name' => "{$prefix}.attributes", 'type' => 'object', 'description' => 'Everything the record holds.'],
        ];

        foreach ($attributes as $attribute) {
            $rows[] = [...$attribute, 'name' => "{$prefix}.attributes.{$attribute['name']}"];
        }

        $rows[] = ['name' => "{$prefix}.links", 'type' => 'object', 'description' => 'Where to find this record.'];
        $rows[] = ['name' => "{$prefix}.links.self", 'type' => 'string', 'description' => 'The absolute address this record is read from.'];

        return $rows;
    }
}
