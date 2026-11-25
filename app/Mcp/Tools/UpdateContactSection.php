<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Write the values of one section of a contact, such as an address or a phone number, and the reminders its dates carry. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateContactSection extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.sections.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'section' => ['required'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);
        $section = $this->section($contact->structureVersion, (string) $validated['section']);

        return $this->answerThroughApi($request, $vault->uuid, $contact->uuid, (string) $section->id);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('The id of the contact, as list-contacts gives it.')->required(),
            'section' => $schema->integer()->description('The id of the section, as get-template-version gives it for the version the contact uses.')->required(),
            'fields' => $schema->object()->description('The values to write, keyed by the id of the field. An address is an object of po_box, extended, street, city, region, postal_code and country, where country is a two letter code (as list-countries gives it), the id of a favourite country, "keep" to leave it as it is, or an empty string for none. A non empty country_name records the name of a place that is not on the list, as written, in place of country. A repeating field, such as phone numbers, is an array of rows, each an object of id (an existing row, left out for a new one), values (keyed by the id of the child field), labels, label_wordings and unknown_year (keyed the same way): [{"values": {"13": "+1 570 555 0100"}, "labels": {"13": 4}}]. Any other key in a row, a child that is not part of the field, and a field that is not in the section are refused.'),
            'labels' => $schema->object()->description('The id of the label chosen for a field that takes one, such as Home or Work, as list-value-lists gives it, keyed by the id of the field.'),
            'label_wordings' => $schema->object()->description('The wording typed for the open label, keyed by the id of the field.'),
            'year_is_unknown' => $schema->object()->description('For a date, true when its year is not known, keyed by the id of the field.'),
            'month_is_unknown' => $schema->object()->description('For a date, true when its month is not known either, keyed by the id of the field.'),
            'new_choices' => $schema->object()->description('Wordings to add to the list of a multiple choice field and pick, keyed by the id of the field.'),
            'reminders' => $schema->object()->description('For a date, such as a birthday, the reminder it carries, keyed by the id of the field, or null to take it away. The object takes cadence (once, weekly, monthly or yearly, yearly when left out), interval, time (HH:MM, 09:00 when left out), timezone and name. An empty object turns on a yearly reminder. The date need not be sent again in fields, and a date left out of reminders keeps its reminder.'),
        ];
    }
}
