<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Models\ValueList;
use Illuminate\Support\Collection;

class FieldCatalogueViewModel
{
    /**
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
     * @return array<int, array{key: string, label: string, description: string, example: string, mark: string, preview: array<string, mixed>, isAvailable: bool}>
     */
    public function types(): array
    {
        return array_merge(...array_map(
            fn (array $family): array => array_map(self::describe(...), $family['types']),
            self::catalogue(),
        ));
    }

    public function total(): int
    {
        return count($this->types());
    }

    /** @return array<string, string> */
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

    /** @return array<int, string> */
    public function countPhrases(): array
    {
        $total = $this->total();

        return array_map(
            fn (int $shown): string => trans_choice(':count of :total type|:count of :total types', $shown, ['total' => $total]),
            range(0, $total),
        );
    }

    public function totalPhrase(): string
    {
        return trans_choice(':count type|:count types', $this->total());
    }

    /** @return array<string, string> */
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

    /** @return array<string, string> */
    public function footerLines(string $section): array
    {
        $lines = [];

        foreach ($this->types() as $type) {
            $lines[$type['key']] = __(':section gets a “:type” field. The type cannot be changed once the field is added. Everything else about it can.', [
                'section' => $section,
                'type' => $type['label'],
            ]);
        }

        return $lines;
    }

    /** @return array<int, string> */
    public function typesNeedingAValueList(): array
    {
        return array_values(array_map(
            fn (FieldTypeEnum $type): string => $type->value,
            array_filter(FieldTypeEnum::cases(), fn (FieldTypeEnum $type): bool => $type->usesValueList()),
        ));
    }

    /** @return array<int, string> */
    public function typesOfferingALabelList(): array
    {
        return array_values(array_map(
            fn (FieldTypeEnum $type): string => $type->value,
            array_filter(FieldTypeEnum::cases(), fn (FieldTypeEnum $type): bool => $type->usesALabelList()),
        ));
    }

    /**
     * @param  Collection<int, ValueList>  $valueLists
     * @return array<string, int>
     */
    public function suppliedLabelListIds(Collection $valueLists): array
    {
        $ids = [];

        foreach (FieldTypeEnum::cases() as $type) {
            $supplied = $type->suppliedLabelList();

            if ($supplied === null) {
                continue;
            }

            $list = $valueLists->firstWhere('key', $supplied->value);

            if ($list instanceof ValueList) {
                $ids[$type->value] = $list->id;
            }
        }

        return $ids;
    }

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
                    ['key' => 'number', 'label' => __('Number'), 'description' => __('A number, with its own minimum, maximum and decimals.'), 'example' => __('Weight in kilograms, 0 to 400, one decimal'), 'mark' => 'hash', 'preview' => ['kind' => 'input', 'value' => __('18.4'), 'mono' => true]],
                    ['key' => 'boolean', 'label' => __('Yes or no'), 'description' => __('A single checkbox. Nothing in between.'), 'example' => __('Neutered'), 'mark' => 'check-circle', 'preview' => ['kind' => 'toggle', 'value' => __('Neutered')]],
                    ['key' => 'url', 'label' => __('URL'), 'description' => __('A web address, checked the same way by the application and the API.'), 'example' => __('The breeder’s website'), 'mark' => 'link', 'preview' => ['kind' => 'input', 'prefix' => 'https://', 'value' => 'breeder.example', 'mono' => true]],
                ],
            ],
            [
                'name' => __('Choice'),
                'blurb' => __('Values picked from a list you control.'),
                'types' => [
                    ['key' => 'select', 'label' => __('Dropdown'), 'description' => __('One option from a value list, in a closed menu.'), 'example' => __('Species: dog, cat, rabbit'), 'mark' => 'chevron-down', 'preview' => ['kind' => 'dropdown', 'value' => __('Dog')]],
                    ['key' => 'multiselect', 'label' => __('Multiple choice'), 'description' => __('Several options from the same value list.'), 'example' => __('Allergies: pollen, chicken, dust'), 'mark' => 'tasks', 'preview' => ['kind' => 'list', 'marker' => 'check', 'options' => [[__('Pollen'), true], [__('Chicken'), true], [__('Dust'), false]]]],
                    ['key' => 'radio', 'label' => __('Radio buttons'), 'description' => __('One option, with all of them visible at once. Best under five.'), 'example' => __('Size: small, medium, large'), 'mark' => 'radio', 'preview' => ['kind' => 'list', 'marker' => 'dot', 'options' => [[__('Small'), false], [__('Medium'), true], [__('Large'), false]]]],
                    ['key' => 'tags', 'label' => __('Free tags'), 'description' => __('Words typed directly, reused across records once they exist.'), 'example' => __('Behaviour notes nobody wants to standardise'), 'mark' => 'tag', 'preview' => ['kind' => 'pills', 'options' => [[__('anxious'), true], [__('good with cats'), true], [__('add a tag'), false]]]],
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
                    ['key' => 'relation', 'label' => __('Record relation'), 'description' => __('Points at another record. You choose what kind it may point at.'), 'example' => __('Veterinarian, pointing at a contact'), 'mark' => 'relationship', 'preview' => ['kind' => 'dropdown', 'value' => __('Dr Patel'), 'caption' => __('contact')]],
                    ['key' => 'qualified_relation', 'label' => __('Qualified relation'), 'description' => __('A link that also says what the two are to each other.'), 'example' => __('Sarah, qualified as “walker of”'), 'mark' => 'relationship', 'preview' => ['kind' => 'dropdown', 'value' => __('Sarah Miller'), 'caption' => __('walker of')]],
                ],
            ],
            [
                'name' => __('File'),
                'blurb' => __('Things uploaded rather than typed.'),
                'types' => [
                    ['key' => 'file', 'label' => __('File'), 'description' => __('Any upload, limited to the file types you accept.'), 'example' => __('Pedigree certificate, PDF'), 'mark' => 'file', 'preview' => ['kind' => 'upload', 'value' => __('Drop a file, or choose one')]],
                    ['key' => 'image', 'label' => __('Image'), 'description' => __('An upload shown as an image on the record.'), 'example' => __('Photo of the microchip paperwork'), 'mark' => 'memory', 'preview' => ['kind' => 'upload', 'value' => __('Drop an image, or choose one')]],
                    ['key' => 'avatar', 'label' => __('Profile photo'), 'description' => __('The single photo shown at the top of the record.'), 'example' => __('The photo of the animal'), 'mark' => 'profile', 'preview' => ['kind' => 'upload', 'round' => true, 'value' => __('Drop a photo, or choose one')]],
                ],
            ],
            [
                'name' => __('Composite'),
                'blurb' => __('Several values that belong together.'),
                'types' => [
                    ['key' => 'email', 'label' => __('Email'), 'description' => __('An email address, checked before it is saved, and what it is for.'), 'example' => __('The kennel’s booking address, marked as work'), 'mark' => 'mail', 'preview' => ['kind' => 'labelled', 'label' => __('Work'), 'value' => 'bookings@kennel.example', 'mono' => true]],
                    ['key' => 'phone', 'label' => __('Phone'), 'description' => __('A phone number, stored as written, and what it is for.'), 'example' => __('The out of hours clinic, marked as mobile'), 'mark' => 'device', 'preview' => ['kind' => 'labelled', 'label' => __('Mobile'), 'value' => __('+44 7700 900461'), 'mono' => true]],
                    ['key' => 'address', 'label' => __('Address'), 'description' => __('Street, city, postcode and country in one block, and what the address is for.'), 'example' => __('Where the kennel is'), 'mark' => 'place', 'preview' => ['kind' => 'rows', 'label' => __('Home'), 'rows' => [[__('Street'), 'full'], [__('City'), 'medium'], [__('Postcode'), 'narrow'], [__('Country'), 'medium']]]],
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
