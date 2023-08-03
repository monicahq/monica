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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    /** @var string */
    protected const ERROR_PARSER = 'ERROR_PARSER';

    /** @var string */
    protected const ERROR_CONTACT_EXIST = 'ERROR_CONTACT_EXIST';

    /** @var string */
    protected const ERROR_CARD_NOT_IMPORTABLE = 'ERROR_CARD_NOT_IMPORTABLE';

    /**
     * Error results.
     *
     * @var array<string,string>
     */
    protected static array $errorResults = [
        self::ERROR_PARSER => 'import_vcard_parse_error',
        self::ERROR_CONTACT_EXIST => 'import_vcard_contact_exist',
        self::ERROR_CARD_NOT_IMPORTABLE => 'import_vcard_not_importable',
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

    public bool $external = false;

    private ?Collection $importers = null;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'contact_id' => 'nullable|uuid|exists:contacts,id',
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
            'uri' => 'nullable|string',
            'external' => 'nullable|boolean',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
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
     */
    public function execute(array $data): array
    {
        $this->validateRules($data);

        $this->external = Arr::get($data, 'external', false);

        if (Arr::get($data, 'contact_id') !== null) {
            $this->validateContactBelongsToVault($data);

            return $this->process($data, $this->contact);
        } else {
            return $this->process($data, null);
        }
    }

    /**
     * Process data importation.
     *
     * @return array<string,mixed>
     */
    private function process(array $data, ?Contact $contact): array
    {
        /**
         * @var VCard|null $entry
         * @var string $vcard
         */
        [$entry, $vcard] = $this->getEntry($data);

        if ($entry === null) {
            return [
                'error' => self::ERROR_PARSER,
                'reason' => static::$errorResults[self::ERROR_PARSER],
                'name' => '(unknow)',
            ];
        }

        return $this->processEntry($data, $contact, $entry, $vcard);
    }

    /**
     * Read the entry and return a VCard object.
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
     * @return array<string,mixed>
     */
    private function processEntry(array $data, ?Contact $contact, VCard $entry, string $vcard): array
    {
        if (! $this->canImportCurrentEntry($entry)) {
            return [
                'error' => self::ERROR_CARD_NOT_IMPORTABLE,
                'reason' => static::$errorResults[self::ERROR_CARD_NOT_IMPORTABLE],
                'name' => $this->name($entry),
            ];
        }

        if ($contact === null) {
            $contact = $this->getExistingContact($data, $entry);
        }

        return $this->processEntryContact($data, $contact, $entry, $vcard);
    }

    /**
     * Process entry importation.
     *
     * @return array<string,mixed>
     */
    private function processEntryContact(array $data, ?Contact $contact, VCard $entry, string $vcard): array
    {
        $behaviour = $data['behaviour'] ?? self::BEHAVIOUR_ADD;
        if ($contact && $behaviour === self::BEHAVIOUR_ADD) {
            return [
                'contact_id' => $contact->id,
                'error' => self::ERROR_CONTACT_EXIST,
                'reason' => static::$errorResults[self::ERROR_CONTACT_EXIST],
                'name' => $this->name($entry),
            ];
        }

        $contact = $this->importEntry($contact, $entry);

        if ($contact === null) {
            return [
                'error' => self::ERROR_CARD_NOT_IMPORTABLE,
                'reason' => static::$errorResults[self::ERROR_CARD_NOT_IMPORTABLE],
                'name' => $this->name($entry),
            ];
        }

        // Save vcard content
        $contact->vcard = $vcard;

        $contact = Contact::withoutTimestamps(function () use ($contact, $data): Contact {
            $uri = Arr::get($data, 'uri');
            if (Arr::get($data, 'external', false)) {
                $contact->distant_etag = Arr::get($data, 'etag');
                $contact->distant_uri = $uri;
            }

            $contact->save();

            return $contact;
        });

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

        return (string) trans('Unknown contact name');
    }

    /**
     * Check whether a contact has a first name or a nickname. If not, contact
     * can not be imported.
     */
    private function canImportCurrentEntry(VCard $entry): bool
    {
        foreach ($this->importers() as $importer) {
            if ($importer->can($entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the contact already exists in the database.
     */
    private function getExistingContact(array $data, VCard $entry): ?Contact
    {
        if ($this->external && $uri = Arr::get($data, 'uri')) {
            return Contact::firstWhere([
                'vault_id' => $this->vault->id,
                'distant_uri' => $uri,
            ]);
        }

        return $this->existingUuid($entry);
    }

    /**
     * Search with uuid.
     */
    private function existingUuid(VCard $entry): ?Contact
    {
        return ! empty($uuid = (string) $entry->UID)
            ?
            Contact::firstWhere([
                'vault_id' => $this->vault->id,
                'distant_uuid' => $uuid,
            ]) ??
            Contact::firstWhere([
                'vault_id' => $this->vault->id,
                'id' => $uuid,
            ])
            : null;
    }

    /**
     * Create the Contact object matching the current entry.
     */
    private function importEntry(?Contact $contact, VCard $entry): ?Contact
    {
        foreach ($this->importers() as $importer) {
            if ($importer->can($entry)) {
                $contact = $importer->import($contact, $entry);
            }
        }

        return $contact;
    }

    /**
     * Get importers instance.
     *
     * @return \Illuminate\Support\Collection<int,ImportVCardResource>
     */
    private function importers(): Collection
    {
        if ($this->importers === null) {
            $this->importers = collect($this->listImporters())
                ->sortBy(fn (ReflectionClass $importer): int => Order::get($importer))
                ->map(fn (ReflectionClass $importer): ImportVCardResource => $importer->newInstance()->setContext($this));
        }

        return $this->importers;
    }

    /**
     * Get importers.
     *
     * @return \Generator<ReflectionClass>
     */
    private function listImporters()
    {
        $namespace = $this->app->getNamespace();
        $appPath = app_path();

        foreach ((new Finder)->files()->in($appPath)->name('*.php')->notName('helpers.php') as $file) {
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
