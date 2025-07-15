<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\ManageLabels\Services\AssignLabel;
use App\Domains\Contact\ManageLabels\Services\RemoveLabel;
use App\Domains\Vault\ManageVaultSettings\Services\CreateLabel;
use App\Models\Contact;
use App\Models\Label;
use Illuminate\Support\Collection;
use Sabre\VObject\Component\VCard;

#[Order(40)]
class ImportLabels extends Importer implements ImportVCardResource
{
    /**
     * Test if the Card is handled by this importer.
     */
    public function handle(VCard $vcard): bool
    {
        return $this->kind($vcard) === 'individual';
    }

    /**
     * Import Contact labels.
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        /** @var Contact $contact */
        $contact = $result;

        $labels = $contact->labels->mapWithKeys(fn (Label $label): array => [$label->name => $label]);
        $categories = $this->getCategories($vcard);

        $toAdd = $categories->diffKeys($labels);
        $toRemove = $labels->diffKeys($categories);

        $refresh = false;
        foreach ($toRemove as $label) {
            $this->removeLabel($contact, $label);
            $refresh = true;
        }
        foreach ($toAdd as $name) {
            $this->addLabel($contact, $name);
            $refresh = true;
        }

        return $refresh ? $contact->refresh() : $contact;
    }

    private function getCategories(VCard $vcard): Collection
    {
        $categories = $vcard->CATEGORIES;

        return $categories === null
            ? collect()
            : collect($categories->getParts());
    }

    private function addLabel(Contact $contact, string $name): void
    {
        $label = $this->getLabel($name) ?? $this->createLabel($name);

        (new AssignLabel)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'label_id' => $label->id,
        ]);
    }

    private function removeLabel(Contact $contact, Label $label): void
    {
        (new RemoveLabel)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'label_id' => $label->id,
        ]);
    }

    private function getLabel(string $name): ?Label
    {
        return $this->vault()->labels()->where('name', $name)->first();
    }

    private function createLabel(string $name): Label
    {
        return (new CreateLabel)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'name' => $name,
        ]);
    }
}
