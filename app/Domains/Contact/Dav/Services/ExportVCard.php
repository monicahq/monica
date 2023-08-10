<?php

namespace App\Domains\Contact\Dav\Services;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\Dav\VCardType;
use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Group;
use App\Services\BaseService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;
use Symfony\Component\Finder\Finder;

class ExportVCard extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'contact_id' => 'required_if:group_id,null|uuid|exists:contacts,id',
            'group_id' => 'required_if:contact_id,null|int|exists:groups,id',
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
            'contact_must_belong_to_vault',
            'group_must_belong_to_vault',
        ];
    }

    public function __construct(
        private Application $app
    ) {
    }

    /**
     * Export one VCard.
     */
    public function execute(array $data): VCard
    {
        $this->validateRules($data);

        if (isset($data['contact_id'])) {
            $obj = $this->contact;
        } elseif (isset($data['group_id'])) {
            $obj = $this->group;
        } else {
            throw new ModelNotFoundException();
        }

        $vcard = $this->export($obj);

        $obj::withoutTimestamps(function () use ($obj, $vcard): void {
            $obj->vcard = $vcard->serialize();
            $obj->save();
        });

        return $vcard;
    }

    /**
     * Export the contact.
     */
    private function export(VCardResource $resource): VCard
    {
        // The standard for most of these fields can be found on https://datatracker.ietf.org/doc/html/rfc6350
        if ($resource->vcard) {
            try {
                /** @var VCard */
                $vcard = Reader::read($resource->vcard, Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
                if (! $vcard->UID) {
                    $vcard->UID = $resource->distant_uuid ?? $resource->uuid ?? $resource->id;
                }
            } catch (ParseException $e) {
                // Ignore error
            }
        }

        if (! isset($vcard)) {
            // Basic information
            $vcard = new VCard([
                'UID' => $resource->uuid ?? $resource->id,
                'SOURCE' => $this->getSource($resource),
                'VERSION' => '4.0',
            ]);
        }

        /** @var Collection<int, ExportVCardResource> */
        $exporters = collect($this->exporters())
            ->filter(fn (ReflectionClass $exporter) => VCardType::is($exporter, $resource::class))
            ->sortBy(fn (ReflectionClass $exporter) => Order::get($exporter))
            ->map(fn (ReflectionClass $exporter): ExportVCardResource => $exporter->newInstance());

        foreach ($exporters as $exporter) {
            $exporter->export($resource, $vcard);
        }

        return $vcard;
    }

    private function getSource(VCardResource $vcard): string
    {
        if ($vcard instanceof Contact) {
            return route('contact.show', [
                'vault' => $vcard->vault,
                'contact' => $vcard,
            ]);
        } elseif ($vcard instanceof Group) {
            return route('group.show', [
                'vault' => $vcard->vault,
                'group' => $vcard,
            ]);
        } else {
            throw new ModelNotFoundException();
        }
    }

    /**
     * Get exporters.
     *
     * @return \Generator<ReflectionClass>
     */
    private function exporters()
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
            if ($class->isSubclassOf(ExportVCardResource::class) && ! $class->isAbstract()) {
                yield $class;
            }
        }
    }
}
