<?php

namespace App\Domains\Contact\Dav\Services;

use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Services\BaseService;
use App\Traits\DAVFormat;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;
use Symfony\Component\Finder\Finder;

class ImportVCard extends BaseService implements ServiceInterface
{
    use DAVFormat;

    /** @var string */
    public const BEHAVIOUR_ADD = 'behaviour_add';

    /** @var string */
    public const BEHAVIOUR_REPLACE = 'behaviour_replace';

    /** @var array */
    protected array $errorResults = [
        'ERROR_PARSER' => 'import_vcard_parse_error',
        'ERROR_CONTACT_EXIST' => 'import_vcard_contact_exist',
        'ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME' => 'import_vcard_contact_no_firstname',
    ];

    /**
     * Valids value for frequency type.
     *
     * @var array<string>
     */
    public static array $behaviourTypes = [
        self::BEHAVIOUR_ADD,
        self::BEHAVIOUR_REPLACE,
    ];

    /**
     * The Account id.
     *
     * @var int
     */
    public int $accountId = 0;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'entry' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! is_string($value) && ! is_resource($value) && ! $value instanceof VCard) {
                        $fail($attribute.' must be a string, a resource, or a VCard object.');
                    }
                },
            ],
            'behaviour' => [
                'required',
                Rule::in(self::$behaviourTypes),
            ],
            'etag' => 'nullable|string',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
            'author_must_be_vault_editor',
        ];
    }

    public function __construct(
        private Application $app
    ) {
    }

    /**
     * Import one VCard.
     *
     * @param  array  $data
     * @return array
     */
    public function execute(array $data): array
    {
        $this->validateRules($data);

        if (Arr::get($data, 'contact_id') !== null) {
            $this->validateContactBelongsToVault($data);

            return $this->process($data, $this->contact);
        } else {
            return $this->process($data, null);
        }
    }

    /**
     * Clear data.
     *
     * @return void
     */
    private function clear(): void
    {
        $this->accountId = 0;
    }

    /**
     * Process data importation.
     *
     * @param  array  $data
     * @param  Contact|null  $contact
     * @return array<string,mixed>
     */
    private function process(array $data, ?Contact $contact): array
    {
        if ($this->accountId !== $data['account_id']) {
            $this->clear();
            $this->accountId = $data['account_id'];
        }

        /**
         * @var VCard|null $entry
         * @var string $vcard
         */
        [$entry, $vcard] = $this->getEntry($data);

        if ($entry === null) {
            return [
                'error' => 'ERROR_PARSER',
                'reason' => $this->errorResults['ERROR_PARSER'],
                'name' => '(unknow)',
            ];
        }

        return $this->processEntry($data, $contact, $entry, $vcard);
    }

    /**
     * Read the entry and return a VCard object.
     *
     * @param  array  $data
     * @return array
     */
    private function getEntry(array $data): array
    {
        $entry = $vcard = $data['entry'];

        if (! $entry instanceof VCard) {
            try {
                $entry = Reader::read($entry, Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
            } catch (ParseException $e) {
                return [null, $vcard];
            }
        }

        if ($vcard instanceof VCard) {
            $vcard = $entry->serialize();
        }

        return [$entry, $vcard];
    }

    /**
     * Process entry importation.
     *
     * @param  array  $data
     * @param  Contact|null  $contact
     * @param  VCard  $entry
     * @param  string  $vcard
     * @return array<string,mixed>
     */
    private function processEntry(array $data, ?Contact $contact, VCard $entry, string $vcard): array
    {
        if (! $this->canImportCurrentEntry($entry)) {
            return [
                'error' => 'ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME',
                'reason' => $this->errorResults['ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME'],
                'name' => $this->name($entry),
            ];
        }

        if ($contact === null) {
            $contact = $this->getExistingContact($entry);
        }

        return $this->processEntryContact($data, $contact, $entry, $vcard);
    }

    /**
     * Process entry importation.
     *
     * @param  array  $data
     * @param  Contact|null  $contact
     * @param  VCard  $entry
     * @param  string  $vcard
     * @return array<string,mixed>
     */
    private function processEntryContact(array $data, ?Contact $contact, VCard $entry, string $vcard): array
    {
        $behaviour = $data['behaviour'] ?? self::BEHAVIOUR_ADD;
        if ($contact && $behaviour === self::BEHAVIOUR_ADD) {
            return [
                'contact_id' => $contact->id,
                'error' => 'ERROR_CONTACT_EXIST',
                'reason' => $this->errorResults['ERROR_CONTACT_EXIST'],
                'name' => $this->name($entry),
            ];
        }

        if ($contact !== null) {
            $timestamps = $contact->timestamps;
            $contact->timestamps = false;
        }

        $contact = $this->importEntry($contact, $entry, $vcard, Arr::get($data, 'etag'));

        if (isset($timestamps)) {
            $contact->timestamps = $timestamps;
        }

        return [
            'contact_id' => $contact->id,
            'name' => $this->name($entry),
        ];
    }

    /**
     * Return the name of current entry.
     * Only used for report display.
     *
     * @param  VCard  $entry
     * @return string
     */
    private function name($entry): string
    {
        if ($entry->N !== null) {
            return trim(implode(' ', $entry->N->getParts()));
        }
        if ($entry->FN !== null) {
            return (string) $entry->FN;
        }
        if ($entry->EMAIL !== null) {
            return (string) $entry->EMAIL;
        }
        if ($entry->NICKNAME !== null) {
            return (string) $entry->NICKNAME;
        }

        return (string) __('Unknown contact name');
    }

    /**
     * Check whether a contact has a first name or a nickname. If not, contact
     * can not be imported.
     *
     * @param  VCard  $entry
     * @return bool
     */
    private function canImportCurrentEntry(VCard $entry): bool
    {
        return
            $this->hasFirstnameInN($entry) ||
            $this->hasNICKNAME($entry) ||
            $this->hasFN($entry);
    }

    /**
     * @param  VCard  $entry
     * @return bool
     */
    private function hasFirstnameInN(VCard $entry): bool
    {
        return $entry->N !== null && ! empty(Arr::get($entry->N->getParts(), '1'));
    }

    /**
     * @param  VCard  $entry
     * @return bool
     */
    private function hasNICKNAME(VCard $entry): bool
    {
        return ! empty((string) $entry->NICKNAME);
    }

    /**
     * @param  VCard  $entry
     * @return bool
     */
    private function hasFN(VCard $entry): bool
    {
        return ! empty((string) $entry->FN);
    }

    /**
     * Check whether the contact already exists in the database.
     *
     * @param  VCard  $entry
     * @return Contact|null
     */
    private function getExistingContact(VCard $entry): ?Contact
    {
        $contact = $this->existingUuid($entry);

        if ($contact) {
            $contact->timestamps = false;
        }

        return $contact;
    }

    /**
     * Search with uuid.
     *
     * @param  VCard  $entry
     * @return Contact|null
     */
    private function existingUuid(VCard $entry): ?Contact
    {
        return ! empty($uuid = (string) $entry->UID) && Uuid::isValid($uuid)
            ? Contact::where([
                'vault_id' => $this->vault->id,
                'uuid' => $uuid,
            ])->first()
            : null;
    }

    /**
     * Create the Contact object matching the current entry.
     *
     * @param  Contact|null  $contact
     * @param  VCard  $entry
     * @param  string  $vcard
     * @param  string|null  $etag
     * @return Contact
     */
    private function importEntry(?Contact $contact, VCard $entry, string $vcard, ?string $etag): Contact
    {
        /** @var \Illuminate\Support\Collection<int, ImportVCardResource> */
        $importers = collect($this->importers())
            ->sortBy(fn (ReflectionClass $importer): int => Order::get($importer))
            ->map(fn (ReflectionClass $importer): ImportVCardResource => $importer->newInstance()->setContext($this));

        foreach ($importers as $importer) {
            $contact = $importer->import($contact, $entry);
        }

        // Save vcard content
        $contact->vcard = $vcard;
        $contact->distant_etag = $etag;

        $contact->save();

        return $contact;
    }

    /**
     * Get importers.
     *
     * @return \Generator<ReflectionClass>
     */
    private function importers()
    {
        $namespace = $this->app->getNamespace();
        $appPath = app_path();

        foreach ((new Finder)->files()->in($appPath)->name('*.php') as $file) {
            $file = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getRealPath(), realpath($appPath).DIRECTORY_SEPARATOR)
            );

            $class = new ReflectionClass($file);
            if ($class->isSubclassOf(ImportVCardResource::class) && ! $class->isAbstract()) {
                yield $class;
            }
        }
    }
}
