<?php

namespace App\Domains\Contact\Dav\Services;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Group;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use ReflectionClass;
use Sabre\VObject\Component\VCard;

class ExportVCard extends BaseService implements ServiceInterface
{
    /** @var Collection<array-key,ExportVCardResource>|null */
    private static ?Collection $exporters = null;

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
            'group_id' => 'required_if:contact_id,null|integer|exists:groups,id',
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
            throw new ModelNotFoundException;
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
            /** @var VCard */
            $vcard = (new ReadVObject)->execute([
                'entry' => $resource->vcard,
            ]);
            if ($vcard !== null && ! $vcard->UID) {
                $vcard->UID = $resource->distant_uuid ?? $resource->uuid ?? $resource->id;
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

        $exporters = $this->exporters($resource::class);

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
            throw new ModelNotFoundException;
        }
    }

    /**
     * Get exporter instances.
     *
     * @param  class-string  $resourceClass
     * @return Collection<array-key,ExportVCardResource>
     */
    private function exporters(string $resourceClass): Collection
    {
        if (self::$exporters === null) {
            self::$exporters = collect(subClasses(ExportVCardResource::class))
                ->sortBy(fn (ReflectionClass $exporter) => Order::get($exporter))
                ->map(fn (ReflectionClass $exporter): ExportVCardResource => $exporter->newInstance());
        }

        return self::$exporters
            ->filter(fn (ExportVCardResource $exporter): bool => $exporter->getType() === $resourceClass);
    }
}
