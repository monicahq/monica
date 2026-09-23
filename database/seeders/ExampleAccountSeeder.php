<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\ArchiveContact;
use App\Actions\CreateAccount;
use App\Actions\CreateContact;
use App\Actions\CreateFieldValueRow;
use App\Actions\CreateReminder;
use App\Actions\SetFieldValue;
use App\Enums\AddressLabelEnum;
use App\Enums\ContactCategoryEnum;
use App\Enums\DefaultFieldEnum;
use App\Enums\EmailLabelEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\PhoneLabelEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Enums\ValueListEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ExampleAccountSeeder extends Seeder
{
    public const string FIRST_NAME = 'Monica';

    public const string LAST_NAME = 'Geller';

    public const string EMAIL = 'monica.geller@centralperk.example';

    public const string PASSWORD = 'password';

    private User $user;

    private Vault $vault;

    /** @var array<string, Field> */
    private array $fields = [];

    public function run(): void
    {
        if (User::query()->where('email', self::EMAIL)->exists()) {
            $this->command?->info('The example account is already there, so nothing was written.');

            return;
        }

        config(['queue.default' => 'sync']);

        config(['mail.default' => 'log']);

        $this->createAccount();
        $this->readTheFields();
        $this->createContacts();

        $this->command?->info('Signed in as '.self::EMAIL.' with the password '.self::PASSWORD.'.');
    }

    private function createAccount(): void
    {
        $this->user = new CreateAccount(
            firstName: self::FIRST_NAME,
            lastName: self::LAST_NAME,
            email: self::EMAIL,
            password: self::PASSWORD,
        )->execute();

        $this->user->markEmailAsVerified();

        $this->vault = $this->user->account->vaults()->firstOrFail();
    }

    private function readTheFields(): void
    {
        $version = $this->vault->defaultTemplates()->firstOrFail()->structureVersion;

        /** @var Collection<int, Field> $fields */
        $fields = $version->assembly->flatMap(fn ($row): Collection => $row->sectionVersion->fields);

        $this->fields = $fields->keyBy('key')->all();
    }

    private function createContacts(): void
    {
        foreach ($this->cast() as $person) {
            $contact = new CreateContact(
                user: $this->user,
                vault: $this->vault,
                firstName: $person['first_name'],
                lastName: $person['last_name'],
                nickname: $person['nickname'],
                isMe: $person['is_me'],
                isFavourite: $person['is_favourite'],
            )->execute();

            $birthday = $this->writeValues($contact, $person);

            if ($birthday instanceof FieldValue && $person['reminder'] !== null) {
                $this->createReminder($birthday, $person['reminder']);
            }

            if ($person['is_archived']) {
                new ArchiveContact(user: $this->user, contact: $contact)->execute();
            }
        }
    }

    /** @param  array<string, mixed>  $person */
    private function writeValues(Contact $contact, array $person): ?FieldValue
    {
        $this->writeChoice($contact, DefaultFieldEnum::Category, ValueListEnum::ContactCategories, $person['category']->value);
        $this->writeChoice($contact, DefaultFieldEnum::Gender, ValueListEnum::Genders, $person['gender']->value);

        if ($person['address'] !== null) {
            [$address, $label] = $person['address'];

            new SetFieldValue(
                user: $this->user,
                valuable: $contact,
                field: $this->fields[DefaultFieldEnum::Address->value],
                value: $address,
                valueListItem: $this->item(ValueListEnum::AddressLabels, $label->value),
            )->execute();
        }

        $repeated = [
            (DefaultFieldEnum::Emails)->value => DefaultFieldEnum::Email,
            (DefaultFieldEnum::Notes)->value => DefaultFieldEnum::Note,
            (DefaultFieldEnum::Phones)->value => DefaultFieldEnum::Phone,
        ];

        foreach ($repeated as $repeater => $field) {
            $this->writeRow($contact, $field, $repeater, $person[$field->value]);
        }

        return $this->writeValue($contact, DefaultFieldEnum::Birthday, $person['birthday']);
    }

    private function writeValue(Contact $contact, DefaultFieldEnum $field, mixed $value): ?FieldValue
    {
        if ($value === null) {
            return null;
        }

        return new SetFieldValue(
            user: $this->user,
            valuable: $contact,
            field: $this->fields[$field->value],
            value: $value,
        )->execute();
    }

    private function writeChoice(Contact $contact, DefaultFieldEnum $field, ValueListEnum $list, string $key): void
    {
        $target = $this->fields[$field->value];
        $chosen = $this->item($list, $key);

        if ($target->type === FieldTypeEnum::Multiselect) {
            new SetFieldValue(
                user: $this->user,
                valuable: $contact,
                field: $target,
                valueListItems: [$chosen],
            )->execute();

            return;
        }

        new SetFieldValue(
            user: $this->user,
            valuable: $contact,
            field: $target,
            valueListItem: $chosen,
        )->execute();
    }

    private function writeRow(Contact $contact, DefaultFieldEnum $field, string $repeater, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $valueListItem = null;
        if (is_array($value)) {
            $valueList = ValueList::query()->where('key', $field->valueList()->value)->first();
            if ($valueList === null) {
                throw new \Exception('Value list not found for field: '.$field->value);
            }
            $valueListItem = $valueList->items()->where('key', $value[1])->first();
            $value = $value[0];
        }

        $row = new CreateFieldValueRow(
            user: $this->user,
            valuable: $contact,
            field: $this->fields[$repeater],
        )->execute();

        new SetFieldValue(
            user: $this->user,
            valuable: $contact,
            field: $this->fields[$field->value],
            row: $row,
            value: $value,
            valueListItem: $valueListItem,
        )->execute();
    }

    /**
     * @param  array{name: string, cadence: ReminderCadenceEnum, moments: array<int, array<array-key, mixed>>}  $reminder
     */
    private function createReminder(FieldValue $birthday, array $reminder): void
    {
        new CreateReminder(
            user: $this->user,
            fieldValue: $birthday,
            cadence: $reminder['cadence'],
            moments: $reminder['moments'],
            timeOfDay: '09:00',
            name: $reminder['name'],
        )->execute();
    }

    private function item(ValueListEnum $list, string $key): ValueListItem
    {
        return ValueListItem::query()
            ->whereIn('value_list_id', $this->user->account->valueLists()->where('key', $list->value)->select('id'))
            ->where('key', $key)
            ->firstOrFail();
    }

    /** @return array<array-key, mixed> */
    private function on(): array
    {
        return ['direction' => ReminderMomentDirectionEnum::On->value, 'amount' => null, 'unit' => null];
    }

    /** @return array<array-key, mixed> */
    private function before(int $amount, ReminderMomentUnitEnum $unit): array
    {
        return ['direction' => ReminderMomentDirectionEnum::Before->value, 'amount' => $amount, 'unit' => $unit->value];
    }

    /** @return list<array<string, mixed>> */
    private function cast(): array
    {
        $apartment20 = ['street' => '90 Bedford Street, apartment 20', 'city' => 'New York', 'postal_code' => 'NY 10014', 'country' => 'United States'];
        $apartment19 = ['street' => '90 Bedford Street, apartment 19', 'city' => 'New York', 'postal_code' => 'NY 10014', 'country' => 'United States'];

        return [
            [
                'first_name' => self::FIRST_NAME,
                'last_name' => self::LAST_NAME,
                'nickname' => null,
                'is_me' => true,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Family,
                'gender' => GenderEnum::Woman,
                'birthday' => '1969-04-22',
                'email' => [self::EMAIL, EmailLabelEnum::Personal],
                'phone' => ['+1 212 555 0120', PhoneLabelEnum::Home],
                'address' => [$apartment20, AddressLabelEnum::Home],
                'note' => 'Head chef. Eleven categories of towel. The cleaning is not a problem, it is a system.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Rachel',
                'last_name' => 'Green',
                'nickname' => 'Rach',
                'is_me' => false,
                'is_favourite' => true,
                'is_archived' => false,
                'category' => ContactCategoryEnum::CloseFriend,
                'gender' => GenderEnum::Woman,
                'birthday' => '1969-05-05',
                'email' => ['rachel.green@fortunata.example', EmailLabelEnum::Personal],
                'phone' => ['+1 212 555 0105', PhoneLabelEnum::Home],
                'address' => [$apartment20, AddressLabelEnum::Home],
                'note' => 'Walked out on Barry and turned up at Central Perk in the wedding dress. Fashion, at last.',
                'reminder' => [
                    'name' => 'Rachel’s birthday',
                    'cadence' => ReminderCadenceEnum::Yearly,
                    'moments' => [$this->before(2, ReminderMomentUnitEnum::Weeks), $this->on()],
                ],
            ],
            [
                'first_name' => 'Ross',
                'last_name' => 'Geller',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => true,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Family,
                'gender' => GenderEnum::Man,
                'birthday' => '1967-10-18',
                'email' => ['ross.geller@prehistorymuseum.example', EmailLabelEnum::Personal],
                'phone' => ['+1 212 555 0118', PhoneLabelEnum::Home],
                'address' => [['street' => '15 Grove Street', 'city' => 'New York', 'postal_code' => 'NY 10014', 'country' => 'United States'], AddressLabelEnum::Home],
                'note' => 'My brother. Palaeontologist. Three divorces and counting. Insists they were on a break.',
                'reminder' => [
                    'name' => 'Ross’s birthday',
                    'cadence' => ReminderCadenceEnum::Yearly,
                    'moments' => [$this->before(3, ReminderMomentUnitEnum::Months), $this->before(1, ReminderMomentUnitEnum::Weeks), $this->on()],
                ],
            ],
            [
                'first_name' => 'Chandler',
                'last_name' => 'Bing',
                'nickname' => 'Chan Chan Man',
                'is_me' => false,
                'is_favourite' => true,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Family,
                'gender' => GenderEnum::Man,
                'birthday' => '1968-04-08',
                'email' => ['chandler.bing@bingstatistical.example', EmailLabelEnum::Work],
                'phone' => ['+1 212 555 0108', PhoneLabelEnum::Mobile],
                'address' => [$apartment20, AddressLabelEnum::Home],
                'note' => 'My husband. Statistical analysis and data reconfiguration, which nobody can repeat back. Hates Thanksgiving, and has since 1978.',
                'reminder' => [
                    'name' => 'Chandler’s birthday',
                    'cadence' => ReminderCadenceEnum::Yearly,
                    'moments' => [$this->before(1, ReminderMomentUnitEnum::Months), $this->before(3, ReminderMomentUnitEnum::Days), $this->on()],
                ],
            ],
            [
                'first_name' => 'Joey',
                'last_name' => 'Tribbiani',
                'nickname' => 'Joe',
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::CloseFriend,
                'gender' => GenderEnum::Man,
                'birthday' => '1968-01-09',
                'email' => ['joey.tribbiani@daysofourlives.example', EmailLabelEnum::Work],
                'phone' => ['+1 212 555 0109', PhoneLabelEnum::Mobile],
                'address' => [$apartment19, AddressLabelEnum::Home],
                'note' => 'Doctor Drake Ramoray on Days of Our Lives. Does not share food. Seven sisters.',
                'reminder' => [
                    'name' => 'Joey’s birthday',
                    'cadence' => ReminderCadenceEnum::Yearly,
                    'moments' => [$this->before(3, ReminderMomentUnitEnum::Months), $this->on()],
                ],
            ],
            [
                'first_name' => 'Phoebe',
                'last_name' => 'Buffay',
                'nickname' => 'Pheebs',
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::CloseFriend,
                'gender' => GenderEnum::Woman,
                'birthday' => '1969-02-16',
                'email' => ['phoebe.buffay@smellycat.example', EmailLabelEnum::Personal],
                'phone' => ['+1 212 555 0116', PhoneLabelEnum::Home],
                'address' => [['street' => '5 Morton Street, apartment 14', 'city' => 'New York', 'postal_code' => 'NY 10014', 'country' => 'United States'], AddressLabelEnum::Home],
                'note' => 'Massage therapist, and the only person I know who wrote Smelly Cat. Twin sister Ursula, who is not to be trusted.',
                'reminder' => [
                    'name' => 'Phoebe’s birthday',
                    'cadence' => ReminderCadenceEnum::Yearly,
                    'moments' => [$this->before(3, ReminderMomentUnitEnum::Months), $this->before(1, ReminderMomentUnitEnum::Weeks)],
                ],
            ],
            [
                'first_name' => 'Mike',
                'last_name' => 'Hannigan',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Friend,
                'gender' => GenderEnum::Man,
                'birthday' => null,
                'email' => ['mike.hannigan@hannigan.example', EmailLabelEnum::Personal],
                'phone' => ['+1 212 555 0132', PhoneLabelEnum::Home],
                'address' => null,
                'note' => 'Married Phoebe in the snow, February 2004. Gave up the law for the piano.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Gunther',
                'last_name' => null,
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Acquaintance,
                'gender' => GenderEnum::Man,
                'birthday' => null,
                'email' => ['gunther@centralperk.example', EmailLabelEnum::Work],
                'phone' => ['+1 212 555 0100', PhoneLabelEnum::Home],
                'address' => [['street' => 'Central Perk, 199 Lafayette Street', 'city' => 'New York', 'postal_code' => 'NY 10012', 'country' => 'United States'], AddressLabelEnum::Work],
                'note' => 'Runs Central Perk. Has been in love with Rachel for years and has told nobody but us.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Janice',
                'last_name' => 'Litman',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Acquaintance,
                'gender' => GenderEnum::Woman,
                'birthday' => null,
                'email' => ['janice.litman@litman.example', EmailLabelEnum::Personal],
                'phone' => ['+1 212 555 0141', PhoneLabelEnum::Home],
                'address' => null,
                'note' => 'Oh. My. God. Chandler keeps ending it and it keeps not ending.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Richard',
                'last_name' => 'Burke',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Friend,
                'gender' => GenderEnum::Man,
                'birthday' => null,
                'email' => ['richard.burke@burkeophthalmology.example', EmailLabelEnum::Personal],
                'phone' => ['+1 212 555 0121', PhoneLabelEnum::Home],
                'address' => null,
                'note' => 'Ophthalmologist, friend of my parents, twenty one years older than me. The moustache stays.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Waltham',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Acquaintance,
                'gender' => GenderEnum::Woman,
                'birthday' => null,
                'email' => ['emily.waltham@waltham.example', EmailLabelEnum::Personal],
                'phone' => ['+44 20 7946 0102', PhoneLabelEnum::Work],
                'address' => [['street' => 'Montague Street', 'city' => 'London', 'postal_code' => 'WC1B 5BP', 'country' => 'United Kingdom'], AddressLabelEnum::Home],
                'note' => 'Ross married her in London in 1998 and said the wrong name at the altar. Mine, in a manner of speaking.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Carol',
                'last_name' => 'Willick',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Friend,
                'gender' => GenderEnum::Woman,
                'birthday' => null,
                'email' => ['carol.willick@willick.example', EmailLabelEnum::Personal],
                'phone' => ['+1 212 555 0111', PhoneLabelEnum::Home],
                'address' => null,
                'note' => 'Ross’s first wife and Ben’s mother. Married Susan in January 1996, and I did the catering.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Susan',
                'last_name' => 'Bunch',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Friend,
                'gender' => GenderEnum::Woman,
                'birthday' => null,
                'email' => ['susan.bunch@bunch.example', EmailLabelEnum::Personal],
                'phone' => ['+1 212 555 0112', PhoneLabelEnum::Home],
                'address' => null,
                'note' => 'Carol’s wife, and Ben’s other mother. Ross has come round to it.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Ben',
                'last_name' => 'Geller',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Family,
                'gender' => GenderEnum::Man,
                'birthday' => '1995-05-11',
                'email' => null,
                'phone' => null,
                'address' => null,
                'note' => 'My nephew. Ross and Carol’s son, raised by Carol and Susan.',
                'reminder' => [
                    'name' => 'Ben’s birthday',
                    'cadence' => ReminderCadenceEnum::Yearly,
                    'moments' => [$this->before(2, ReminderMomentUnitEnum::Months), $this->on()],
                ],
            ],
            [
                'first_name' => 'Emma',
                'last_name' => 'Geller Green',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Family,
                'gender' => GenderEnum::Woman,
                'birthday' => '2002-05-16',
                'email' => null,
                'phone' => null,
                'address' => null,
                'note' => 'My niece. Ross and Rachel’s daughter, born the day after Monica and Chandler’s wedding anniversary.',
                'reminder' => [
                    'name' => 'Emma’s birthday',
                    'cadence' => ReminderCadenceEnum::Yearly,
                    'moments' => [$this->before(3, ReminderMomentUnitEnum::Months), $this->before(1, ReminderMomentUnitEnum::Months), $this->on()],
                ],
            ],
            [
                'first_name' => 'Jack',
                'last_name' => 'Geller',
                'nickname' => 'Dad',
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Family,
                'gender' => GenderEnum::Man,
                'birthday' => null,
                'email' => ['jack.geller@geller.example', EmailLabelEnum::Personal],
                'phone' => ['+1 516 555 0130', PhoneLabelEnum::Home],
                'address' => [['street' => null, 'city' => 'Long Island', 'postal_code' => 'NY 11501', 'country' => 'United States'], AddressLabelEnum::Home],
                'note' => 'Dad. Sold the Porsche story to anybody who would listen.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Judy',
                'last_name' => 'Geller',
                'nickname' => 'Mum',
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => false,
                'category' => ContactCategoryEnum::Family,
                'gender' => GenderEnum::Woman,
                'birthday' => null,
                'email' => ['judy.geller@geller.example', EmailLabelEnum::Personal],
                'phone' => ['+1 516 555 0131', PhoneLabelEnum::Home],
                'address' => [['street' => null, 'city' => 'Long Island', 'postal_code' => 'NY 11501', 'country' => 'United States'], AddressLabelEnum::Home],
                'note' => 'Mum. Ross is the favourite and she has never once pretended otherwise.',
                'reminder' => null,
            ],
            [
                'first_name' => 'Mr.',
                'last_name' => 'Heckles',
                'nickname' => null,
                'is_me' => false,
                'is_favourite' => false,
                'is_archived' => true,
                'category' => ContactCategoryEnum::Acquaintance,
                'gender' => GenderEnum::Man,
                'birthday' => null,
                'email' => null,
                'phone' => '+1 212 555 0119',
                'address' => [['street' => '90 Bedford Street', 'city' => 'New York', 'postal_code' => 'NY 10014', 'country' => 'United States'], AddressLabelEnum::Home],
                'note' => 'Lived below us and banged on the ceiling with a broom. Died in 1995 and left us everything he had.',
                'reminder' => null,
            ],
        ];
    }
}
