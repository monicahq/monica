<?php

namespace App\Domains\Contact\ManageJobInformation\Web\Controllers;

use App\Domains\Contact\ManageJobInformation\Services\ResetJobInformation;
use App\Domains\Contact\ManageJobInformation\Services\UpdateJobInformation;
use App\Domains\Contact\ManageJobInformation\Web\ViewHelpers\ModuleCompanyViewHelper;
use App\Domains\Vault\ManageCompanies\Services\CreateCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleJobInformationController extends Controller
{
    public function index(Request $request, string $vaultId, string $contactId): JsonResponse
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);
        $collection = ModuleCompanyViewHelper::list($vault, $contact);

        return response()->json([
            'data' => $collection,
        ], 200);
    }

    public function update(Request $request, string $vaultId, string $contactId)
    {
        $companyId = 0;
        if ($request->input('company_id')) {
            $company = Company::findOrFail($request->input('company_id'));
            $companyId = $company->id;
        }

        if ($request->input('company_name')) {
            $data = [
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::id(),
                'vault_id' => $vaultId,
                'name' => $request->input('company_name'),
                'type' => Company::TYPE_COMPANY,
            ];

            $company = (new CreateCompany)->execute($data);
            $companyId = $company->id;
        }

        (new UpdateJobInformation)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'company_id' => $companyId,
            'job_position' => $request->input('job_position'),
        ]);

        $contact = Contact::findOrFail($contactId);

        return response()->json([
            'data' => ModuleCompanyViewHelper::data($contact),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId)
    {
        (new ResetJobInformation)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ]);

        $contact = Contact::findOrFail($contactId);

        return response()->json([
            'data' => ModuleCompanyViewHelper::data($contact),
        ], 200);
    }
}
