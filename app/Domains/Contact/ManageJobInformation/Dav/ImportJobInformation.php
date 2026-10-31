<?php

namespace App\Domains\Contact\ManageJobInformation\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\ManageJobInformation\Services\UpdateJobInformation;
use App\Domains\Vault\ManageCompanies\Services\CreateCompany;
use App\Models\Company;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

/**
 * Import Job Information from vCard ORG field.
 * 
 * This importer handles the ORG (organization) field from vCard files and creates
 * or links the contact to a company in Monica's job information system.
 * 
 * Note: This uses the separate Job Information system (contacts.company_id) and NOT
 * the ContactInformation system. Organizations are stored as Company models and linked
 * to contacts through the company_id field.
 * 
 * @see UpdateJobInformation Service used to update job information
 * @see CreateCompany Service used to create new companies
 */
#[Order(41)]
class ImportJobInformation extends Importer implements ImportVCardResource
{
    /**
     * Test if the Card is handled by this importer.
     * 
     * @param VCard $vcard The vCard to test
     * @return bool True if this is an individual contact (not organization)
     */
    public function handle(VCard $vcard): bool
    {
        return $this->kind($vcard) === 'individual';
    }

    /**
     * Import Contact job information from ORG field.
     * 
     * Extracts the organization name from the vCard ORG field, finds or creates
     * the corresponding Company in Monica, and links it to the contact.
     * 
     * @param VCard $vcard The vCard being imported
     * @param VCardResource|null $result The contact resource (Contact model)
     * @return VCardResource|null The updated contact with job information, or null
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        /** @var Contact $contact */
        $contact = $result;

        if (! isset($vcard->ORG)) {
            return $contact;
        }

        $orgValue = $this->formatValue((string) $vcard->ORG);
        if (empty($orgValue)) {
            return $contact;
        }

        // Try to find or create company
        $company = $this->vault()->companies()
            ->where('name', $orgValue)
            ->first();

        if (! $company) {
            $company = (new CreateCompany)->execute([
                'account_id' => $this->account()->id,
                'author_id' => $this->author()->id,
                'vault_id' => $this->vault()->id,
                'name' => $orgValue,
                'type' => Company::TYPE_COMPANY,
            ]);
        }

        // Update contact's job information
        (new UpdateJobInformation)->execute([
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'job_position' => null,
        ]);

        return $contact->refresh();
    }
}
