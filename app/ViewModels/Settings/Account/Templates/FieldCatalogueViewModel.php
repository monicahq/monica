<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;

/**
 * The catalogue somebody picks a field type from, and the words that describe
 * each one.
 *
 * The type is the one decision about a field that cannot be taken back later,
 * so the screen that asks for it says what each type is for and gives an
 * example rather than listing bare names. That is a screen's worth of copy, and
 * it lives here rather than on the enum: the enum says what a type does, and
 * this says what it is for.
 *
 * The families the first version does not ship are listed too, marked as not
 * available. Somebody deciding how to record a price is better served by
 * reading that money is coming than by inventing a workaround nobody will
 * revisit. Whether a type can actually be picked is asked of the enum, so the
 * two cannot drift: anything absent from `FieldTypeEnum` is offered as a
 * preview and nothing more.
 */
class FieldCatalogueViewModel
{
    /**
     * Every family, in the order the picker shows them.
     *
     * @return array<int, array{name: string, blurb: string, types: array<int, array{key: string, label: string, description: string, example: string, isAvailable: bool}>}>
     */
    public function families(): array
    {
        return array_map(fn (array $family): array => [
            'name' => $family['name'],
            'blurb' => $family['blurb'],
            'types' => array_map(fn (array $type): array => [
                'key' => $type['key'],
                'label' => $type['label'],
                'description' => $type['description'],
                'example' => $type['example'],
                'isAvailable' => FieldTypeEnum::tryFrom($type['key']) !== null,
            ], $family['types']),
        ], self::catalogue());
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
     * @return array<int, array{name: string, blurb: string, types: array<int, array{key: string, label: string, description: string, example: string}>}>
     */
    private static function catalogue(): array
    {
        return [
            [
                'name' => __('Simple'),
                'blurb' => __('One value, typed straight in.'),
                'types' => [
                    ['key' => 'text', 'label' => __('Short text'), 'description' => __('One line of text, up to a length you set.'), 'example' => __('Nickname, microchip number, collar colour')],
                    ['key' => 'textarea', 'label' => __('Long text'), 'description' => __('Several lines, for anything that needs a paragraph.'), 'example' => __('What the vet said at the last visit')],
                    ['key' => 'number', 'label' => __('Number'), 'description' => __('A number, with its own floor, ceiling and decimals.'), 'example' => __('Weight in kilograms, 0 to 400, one decimal')],
                    ['key' => 'boolean', 'label' => __('Yes or no'), 'description' => __('A single checkbox. Nothing in between.'), 'example' => __('Neutered')],
                    ['key' => 'url', 'label' => __('URL'), 'description' => __('A web address, checked the same way by the interface and the API.'), 'example' => __('The breeder’s website')],
                    ['key' => 'email', 'label' => __('Email'), 'description' => __('An email address, checked before it is saved.'), 'example' => __('The kennel’s booking address')],
                    ['key' => 'phone', 'label' => __('Phone'), 'description' => __('A phone number, stored as written.'), 'example' => __('The out of hours clinic')],
                ],
            ],
            [
                'name' => __('Choice'),
                'blurb' => __('Values picked from a list you control.'),
                'types' => [
                    ['key' => 'select', 'label' => __('Dropdown'), 'description' => __('One option from a value list, in a closed menu.'), 'example' => __('Species: dog, cat, rabbit')],
                    ['key' => 'multiselect', 'label' => __('Multiple choice'), 'description' => __('Several options from the same value list.'), 'example' => __('Allergies: pollen, chicken, dust')],
                    ['key' => 'radio', 'label' => __('Radio buttons'), 'description' => __('One option, with all of them visible at once. Best under five.'), 'example' => __('Size: small, medium, large')],
                    ['key' => 'tags', 'label' => __('Free tags'), 'description' => __('Words typed on the spot, reused across records once they exist.'), 'example' => __('Behaviour notes nobody wants to standardise')],
                ],
            ],
            [
                'name' => __('Date'),
                'blurb' => __('Points in time, and what they trigger.'),
                'types' => [
                    ['key' => 'date', 'label' => __('Date'), 'description' => __('A precise day.'), 'example' => __('Adoption day')],
                    ['key' => 'recurring_date', 'label' => __('Recurring date'), 'description' => __('A day that comes back every year. The year may be unknown, and it can drive a reminder.'), 'example' => __('Birthday, yearly check up')],
                    ['key' => 'duration', 'label' => __('Duration'), 'description' => __('A length of time rather than a day.'), 'example' => __('How long the walk usually takes')],
                ],
            ],
            [
                'name' => __('Relation'),
                'blurb' => __('Links to other records in the vault.'),
                'types' => [
                    ['key' => 'relation', 'label' => __('Contact or entity relation'), 'description' => __('Points at another record. You choose what kind it may point at.'), 'example' => __('Veterinarian, pointing at a contact')],
                    ['key' => 'qualified_relation', 'label' => __('Qualified relation'), 'description' => __('A link that also says what the two are to each other.'), 'example' => __('Sarah, qualified as “walker of”')],
                ],
            ],
            [
                'name' => __('File'),
                'blurb' => __('Things uploaded rather than typed.'),
                'types' => [
                    ['key' => 'file', 'label' => __('File'), 'description' => __('Any upload, limited to the file types you accept.'), 'example' => __('Pedigree certificate, PDF')],
                    ['key' => 'image', 'label' => __('Image'), 'description' => __('An upload shown as a picture on the record.'), 'example' => __('Photo of the microchip paperwork')],
                    ['key' => 'avatar', 'label' => __('Avatar'), 'description' => __('The single picture shown at the top of the record.'), 'example' => __('The portrait of the animal')],
                ],
            ],
            [
                'name' => __('Composite'),
                'blurb' => __('Several values that belong together.'),
                'types' => [
                    ['key' => 'address', 'label' => __('Address'), 'description' => __('Street, city, postcode and country in one block.'), 'example' => __('Where the kennel is')],
                    ['key' => 'repeater', 'label' => __('Repeater'), 'description' => __('A group of fields that can be filled again and again. A repeater inside a repeater is refused.'), 'example' => __('Vaccinations: vaccine, date, next due')],
                    ['key' => 'money', 'label' => __('Money'), 'description' => __('An amount with its currency.'), 'example' => __('What the insurance costs a year')],
                    ['key' => 'full_name', 'label' => __('Full name'), 'description' => __('First, middle and last name, sorted properly.'), 'example' => __('The name on the pedigree')],
                ],
            ],
            [
                'name' => __('Layout'),
                'blurb' => __('Structure. These store nothing and are skipped by search and export.'),
                'types' => [
                    ['key' => 'divider', 'label' => __('Divider'), 'description' => __('A line between two groups of fields.'), 'example' => __('Between health and care')],
                    ['key' => 'heading', 'label' => __('Heading'), 'description' => __('A title inside the section.'), 'example' => __('“Appointments” above the repeater')],
                    ['key' => 'help', 'label' => __('Help text'), 'description' => __('A note to whoever fills the form.'), 'example' => __('“Ask the vet for the batch number.”')],
                ],
            ],
            [
                'name' => __('Other'),
                'blurb' => __('Coming after the first version.'),
                'types' => [
                    ['key' => 'rating', 'label' => __('Rating'), 'description' => __('A score on a scale you set.'), 'example' => __('How well the last groomer went')],
                    ['key' => 'rich_text', 'label' => __('Rich text'), 'description' => __('Long text with bold, lists and links.'), 'example' => __('A full care routine')],
                    ['key' => 'colour', 'label' => __('Colour'), 'description' => __('A colour value, picked rather than typed.'), 'example' => __('Coat colour on a chart')],
                    ['key' => 'location', 'label' => __('Geographic location'), 'description' => __('A point on a map, with coordinates.'), 'example' => __('Where the dog was found')],
                ],
            ],
            [
                'name' => __('Calculated'),
                'blurb' => __('Filled by the system, not by a person.'),
                'types' => [
                    ['key' => 'computed', 'label' => __('Computed value'), 'description' => __('Worked out from other fields every time the record is read.'), 'example' => __('Age, from the birthday')],
                ],
            ],
        ];
    }
}
