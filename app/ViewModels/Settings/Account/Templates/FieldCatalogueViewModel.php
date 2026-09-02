<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;

/**
 * The catalogue somebody picks a field type from. The type is the one
 * decision about a field that cannot be taken back later, so the screen says
 * what each one is for, gives an example, and draws the field the type
 * produces, rather than listing bare names. That copy lives here rather than
 * on the enum: the enum says what a type does, and this says what it is for.
 *
 * The families the first version does not ship are listed too, marked as not
 * available. Whether a type can actually be picked is asked of
 * `FieldTypeEnum`, so the two cannot drift.
 *
 * Three things about the shape of an entry are worth knowing before editing it.
 *
 * The mark is the name of a glyph in the icon component, and it draws the
 * control the type produces rather than the subject it is about, because that
 * is what tells two neighbours apart: a short text and a long text are both
 * text, and what differs is the box. A mark may be used twice, since the label
 * beside it carries the meaning and the mark only helps somebody find a card
 * again.
 *
 * The preview is the shape of an illustration, not the illustration itself. It
 * names one of nine kinds and carries only what that kind draws, and
 * `_field-type-preview.blade.php` is what turns it into markup.
 *
 * Every sample value inside a preview goes through `__()`, including the ones
 * that look like data rather than prose. A date, a number and an amount are
 * written differently in every locale, so the only way a translator can get
 * them right is to be handed them. An email address, a domain and a pair of
 * coordinates stay as they are, because an address is not translated.
 */
class FieldCatalogueViewModel
{
    /**
     * Every family, in the order the picker shows them.
     *
     * @return array<int, array{name: string, blurb: string, types: array<int, array{key: string, label: string, description: string, example: string, mark: string, preview: array<string, mixed>, isAvailable: bool}>}>
     */
    public function families(): array
    {
        return array_map(fn (array $family): array => [
            'name' => $family['name'],
            'blurb' => $family['blurb'],
            'types' => array_map(self::describe(...), $family['types']),
        ], self::catalogue());
    }

    /**
     * Every type, in the same order, with the families flattened away. The
     * panel draws one illustration per type and has no use for the grouping.
     *
     * @return array<int, array{key: string, label: string, description: string, example: string, mark: string, preview: array<string, mixed>, isAvailable: bool}>
     */
    public function types(): array
    {
        return array_merge(...array_map(
            fn (array $family): array => array_map(self::describe(...), $family['types']),
            self::catalogue(),
        ));
    }

    /**
     * How many types the catalogue names, which is not how many the enum ships.
     */
    public function total(): int
    {
        return count($this->types());
    }

    /**
     * What the filter matches a query against, by key. Lowercased here and
     * lowercased again in the browser, the same way the API reference filters
     * its endpoints, so both sides agree without either of them holding a copy
     * of the other's rules.
     *
     * @return array<string, string>
     */
    public function searchIndex(): array
    {
        $index = [];

        foreach ($this->types() as $type) {
            $index[$type['key']] = mb_strtolower(
                "{$type['label']} {$type['key']} {$type['description']} {$type['example']}"
            );
        }

        return $index;
    }

    /**
     * The count line, once for every count it could show.
     *
     * The number changes in the browser and the wording depends on the number,
     * so the server writes every wording the screen might need and the browser
     * picks one by index. Pluralising in JavaScript would put a grammar rule
     * somewhere no translator can reach it.
     *
     * @return array<int, string>
     */
    public function countPhrases(): array
    {
        $total = $this->total();

        return array_map(
            fn (int $shown): string => trans_choice(':count of :total type|:count of :total types', $shown, ['total' => $total]),
            range(0, $total),
        );
    }

    /**
     * The count line with no filter applied, which says how many there are
     * rather than how many of them are showing.
     */
    public function totalPhrase(): string
    {
        return trans_choice(':count type|:count types', $this->total());
    }

    /**
     * One sentence per type, for the region that tells a screen reader what the
     * panel is previewing. A panel swapped behind `x-show` is not announced on
     * its own.
     *
     * @return array<string, string>
     */
    public function sentences(): array
    {
        $sentences = [];

        foreach ($this->types() as $type) {
            $sentences[$type['key']] = $type['isAvailable']
                ? "{$type['label']}. {$type['description']}"
                : "{$type['label']}. {$type['description']} ".__('Not available yet.');
        }

        return $sentences;
    }

    /**
     * What the footer says once a type is chosen, one sentence per type.
     *
     * The label is quoted rather than dropped into the sentence bare. A type is
     * named with a capital letter, and lowercasing one in the browser is how a
     * Turkish label loses its dot.
     *
     * @return array<string, string>
     */
    public function footerLines(string $section): array
    {
        $lines = [];

        foreach ($this->types() as $type) {
            $lines[$type['key']] = __(':section gets a “:type” field. The type is settled once the field is added, and everything else about it can change later.', [
                'section' => $section,
                'type' => $type['label'],
            ]);
        }

        return $lines;
    }

    /**
     * The types that cannot be added without saying which list their choices
     * come from, so the picker can ask for one at the same time rather than
     * refusing afterwards.
     *
     * @return array<int, string>
     */
    public function typesNeedingAValueList(): array
    {
        return array_values(array_map(
            fn (FieldTypeEnum $type): string => $type->value,
            array_filter(FieldTypeEnum::cases(), fn (FieldTypeEnum $type): bool => $type->usesValueList()),
        ));
    }

    /**
     * What a type is called, for the field list and the inspector. A type the
     * catalogue has no words for reads as its own key, which only happens while
     * a type is being added to the enum and the copy has not caught up.
     */
    public static function labelFor(FieldTypeEnum $type): string
    {
        foreach (self::catalogue() as $family) {
            foreach ($family['types'] as $candidate) {
                if ($candidate['key'] === $type->value) {
                    return $candidate['label'];
                }
            }
        }

        return $type->value;
    }

    /**
     * @param  array{key: string, label: string, description: string, example: string, mark: string, preview: array<string, mixed>}  $type
     * @return array{key: string, label: string, description: string, example: string, mark: string, preview: array<string, mixed>, isAvailable: bool}
     */
    private static function describe(array $type): array
    {
        return [
            'key' => $type['key'],
            'label' => $type['label'],
            'description' => $type['description'],
            'example' => $type['example'],
            'mark' => $type['mark'],
            'preview' => $type['preview'],
            'isAvailable' => FieldTypeEnum::tryFrom($type['key']) !== null,
        ];
    }

    /**
     * @return array<int, array{name: string, blurb: string, types: array<int, array{key: string, label: string, description: string, example: string, mark: string, preview: array<string, mixed>}>}>
     */
    private static function catalogue(): array
    {
        return [
            [
                'name' => __('Simple'),
                'blurb' => __('One value, typed straight in.'),
                'types' => [
                    ['key' => 'text', 'label' => __('Short text'), 'description' => __('One line of text, up to a length you set.'), 'example' => __('Nickname, microchip number, collar colour'), 'mark' => 'text', 'preview' => ['kind' => 'input', 'value' => __('Biscuit')]],
                    ['key' => 'textarea', 'label' => __('Long text'), 'description' => __('Several lines, for anything that needs a paragraph.'), 'example' => __('What the vet said at the last visit'), 'mark' => 'text-lines', 'preview' => ['kind' => 'textarea']],
                    ['key' => 'number', 'label' => __('Number'), 'description' => __('A number, with its own floor, ceiling and decimals.'), 'example' => __('Weight in kilograms, 0 to 400, one decimal'), 'mark' => 'hash', 'preview' => ['kind' => 'input', 'value' => __('18.4'), 'mono' => true]],
                    ['key' => 'boolean', 'label' => __('Yes or no'), 'description' => __('A single checkbox. Nothing in between.'), 'example' => __('Neutered'), 'mark' => 'check-circle', 'preview' => ['kind' => 'toggle', 'value' => __('Neutered')]],
                    ['key' => 'url', 'label' => __('URL'), 'description' => __('A web address, checked the same way by the interface and the API.'), 'example' => __('The breeder’s website'), 'mark' => 'link', 'preview' => ['kind' => 'input', 'prefix' => 'https://', 'value' => 'breeder.example', 'mono' => true]],
                    ['key' => 'email', 'label' => __('Email'), 'description' => __('An email address, checked before it is saved.'), 'example' => __('The kennel’s booking address'), 'mark' => 'mail', 'preview' => ['kind' => 'input', 'value' => 'bookings@kennel.example', 'mono' => true]],
                    ['key' => 'phone', 'label' => __('Phone'), 'description' => __('A phone number, stored as written.'), 'example' => __('The out of hours clinic'), 'mark' => 'device', 'preview' => ['kind' => 'input', 'value' => __('+44 7700 900461'), 'mono' => true]],
                ],
            ],
            [
                'name' => __('Choice'),
                'blurb' => __('Values picked from a list you control.'),
                'types' => [
                    ['key' => 'select', 'label' => __('Dropdown'), 'description' => __('One option from a value list, in a closed menu.'), 'example' => __('Species: dog, cat, rabbit'), 'mark' => 'chevron-down', 'preview' => ['kind' => 'dropdown', 'value' => __('Dog')]],
                    ['key' => 'multiselect', 'label' => __('Multiple choice'), 'description' => __('Several options from the same value list.'), 'example' => __('Allergies: pollen, chicken, dust'), 'mark' => 'tasks', 'preview' => ['kind' => 'list', 'marker' => 'check', 'options' => [[__('Pollen'), true], [__('Chicken'), true], [__('Dust'), false]]]],
                    ['key' => 'radio', 'label' => __('Radio buttons'), 'description' => __('One option, with all of them visible at once. Best under five.'), 'example' => __('Size: small, medium, large'), 'mark' => 'radio', 'preview' => ['kind' => 'list', 'marker' => 'dot', 'options' => [[__('Small'), false], [__('Medium'), true], [__('Large'), false]]]],
                    ['key' => 'tags', 'label' => __('Free tags'), 'description' => __('Words typed on the spot, reused across records once they exist.'), 'example' => __('Behaviour notes nobody wants to standardise'), 'mark' => 'tag', 'preview' => ['kind' => 'pills', 'options' => [[__('anxious'), true], [__('good with cats'), true], [__('add a tag'), false]]]],
                ],
            ],
            [
                'name' => __('Date'),
                'blurb' => __('Points in time, and what they trigger.'),
                'types' => [
                    ['key' => 'date', 'label' => __('Date'), 'description' => __('A precise day.'), 'example' => __('Adoption day'), 'mark' => 'calendar', 'preview' => ['kind' => 'input', 'value' => __('14 June 2019')]],
                    ['key' => 'recurring_date', 'label' => __('Recurring date'), 'description' => __('A day that comes back every year. The year may be unknown, and it can drive a reminder.'), 'example' => __('Birthday, yearly check up'), 'mark' => 'calendar-repeat', 'preview' => ['kind' => 'input', 'value' => __('14 June, every year')]],
                    ['key' => 'duration', 'label' => __('Duration'), 'description' => __('A length of time rather than a day.'), 'example' => __('How long the walk usually takes'), 'mark' => 'clock', 'preview' => ['kind' => 'input', 'value' => __('45 minutes')]],
                ],
            ],
            [
                'name' => __('Relation'),
                'blurb' => __('Links to other records in the vault.'),
                'types' => [
                    ['key' => 'relation', 'label' => __('Contact or entity relation'), 'description' => __('Points at another record. You choose what kind it may point at.'), 'example' => __('Veterinarian, pointing at a contact'), 'mark' => 'relationship', 'preview' => ['kind' => 'dropdown', 'value' => __('Dr Patel'), 'caption' => __('contact')]],
                    ['key' => 'qualified_relation', 'label' => __('Qualified relation'), 'description' => __('A link that also says what the two are to each other.'), 'example' => __('Sarah, qualified as “walker of”'), 'mark' => 'relationship', 'preview' => ['kind' => 'dropdown', 'value' => __('Sarah Miller'), 'caption' => __('walker of')]],
                ],
            ],
            [
                'name' => __('File'),
                'blurb' => __('Things uploaded rather than typed.'),
                'types' => [
                    ['key' => 'file', 'label' => __('File'), 'description' => __('Any upload, limited to the file types you accept.'), 'example' => __('Pedigree certificate, PDF'), 'mark' => 'file', 'preview' => ['kind' => 'upload', 'value' => __('Drop a file, or choose one')]],
                    ['key' => 'image', 'label' => __('Image'), 'description' => __('An upload shown as a picture on the record.'), 'example' => __('Photo of the microchip paperwork'), 'mark' => 'memory', 'preview' => ['kind' => 'upload', 'value' => __('Drop a picture, or choose one')]],
                    ['key' => 'avatar', 'label' => __('Avatar'), 'description' => __('The single picture shown at the top of the record.'), 'example' => __('The portrait of the animal'), 'mark' => 'profile', 'preview' => ['kind' => 'upload', 'round' => true, 'value' => __('Drop a portrait, or choose one')]],
                ],
            ],
            [
                'name' => __('Composite'),
                'blurb' => __('Several values that belong together.'),
                'types' => [
                    ['key' => 'address', 'label' => __('Address'), 'description' => __('Street, city, postcode and country in one block.'), 'example' => __('Where the kennel is'), 'mark' => 'place', 'preview' => ['kind' => 'rows', 'rows' => [[__('Street'), 'full'], [__('City'), 'medium'], [__('Postcode'), 'narrow']]]],
                    ['key' => 'repeater', 'label' => __('Repeater'), 'description' => __('A group of fields that can be filled again and again. A repeater inside a repeater is refused.'), 'example' => __('Vaccinations: vaccine, date, next due'), 'mark' => 'layers', 'preview' => ['kind' => 'rows', 'repeats' => true, 'rows' => [[__('Vaccine'), 'full'], [__('Date given'), 'wide'], [__('Next due'), 'narrow']]]],
                    ['key' => 'money', 'label' => __('Money'), 'description' => __('An amount with its currency.'), 'example' => __('What the insurance costs a year'), 'mark' => 'coin', 'preview' => ['kind' => 'input', 'prefix' => __('GBP'), 'value' => __('40.00'), 'mono' => true]],
                    ['key' => 'full_name', 'label' => __('Full name'), 'description' => __('First, middle and last name, sorted properly.'), 'example' => __('The name on the pedigree'), 'mark' => 'profile', 'preview' => ['kind' => 'rows', 'rows' => [[__('First'), 'medium'], [__('Middle'), 'narrow'], [__('Last'), 'medium']]]],
                ],
            ],
            [
                'name' => __('Layout'),
                'blurb' => __('Structure. These store nothing and are skipped by search and export.'),
                'types' => [
                    ['key' => 'divider', 'label' => __('Divider'), 'description' => __('A line between two groups of fields.'), 'example' => __('Between health and care'), 'mark' => 'divider', 'preview' => ['kind' => 'layout', 'layout' => 'divider']],
                    ['key' => 'heading', 'label' => __('Heading'), 'description' => __('A title inside the section.'), 'example' => __('“Appointments” above the repeater'), 'mark' => 'heading', 'preview' => ['kind' => 'layout', 'layout' => 'heading', 'value' => __('Appointments')]],
                    ['key' => 'help', 'label' => __('Help text'), 'description' => __('A note to whoever fills the form.'), 'example' => __('“Ask the vet for the batch number.”'), 'mark' => 'info', 'preview' => ['kind' => 'layout', 'layout' => 'help', 'value' => __('Ask the vet for the batch number.')]],
                ],
            ],
            [
                'name' => __('Other'),
                'blurb' => __('Coming after the first version.'),
                'types' => [
                    ['key' => 'rating', 'label' => __('Rating'), 'description' => __('A score on a scale you set.'), 'example' => __('How well the last groomer went'), 'mark' => 'star', 'preview' => ['kind' => 'pills', 'options' => [['1', false], ['2', false], ['3', true], ['4', false], ['5', false]]]],
                    ['key' => 'rich_text', 'label' => __('Rich text'), 'description' => __('Long text with bold, lists and links.'), 'example' => __('A full care routine'), 'mark' => 'note', 'preview' => ['kind' => 'textarea']],
                    ['key' => 'colour', 'label' => __('Colour'), 'description' => __('A colour value, picked rather than typed.'), 'example' => __('Coat colour on a chart'), 'mark' => 'swatch', 'preview' => ['kind' => 'input', 'value' => __('Chocolate brown')]],
                    ['key' => 'location', 'label' => __('Geographic location'), 'description' => __('A point on a map, with coordinates.'), 'example' => __('Where the dog was found'), 'mark' => 'place', 'preview' => ['kind' => 'input', 'value' => '51.5072, -0.1276', 'mono' => true]],
                ],
            ],
            [
                'name' => __('Calculated'),
                'blurb' => __('Filled by the system, not by a person.'),
                'types' => [
                    ['key' => 'computed', 'label' => __('Computed value'), 'description' => __('Worked out from other fields every time the record is read.'), 'example' => __('Age, from the birthday'), 'mark' => 'settings', 'preview' => ['kind' => 'input', 'value' => __('6 years old')]],
                ],
            ],
        ];
    }
}
