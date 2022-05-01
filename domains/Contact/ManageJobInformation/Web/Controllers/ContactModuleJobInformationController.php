<?php

namespace App\Contact\ManageJobInformation\Web\Controllers;

use App\Models\Vault;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Vault\ManageCompanies\Services\CreateCompany;
use App\Contact\ManageJobInformation\Services\UpdateJobInformation;
use App\Contact\ManageJobInformation\Web\ViewHelpers\ModuleCompanyViewHelper;

class ContactModuleJobInformationController extends Controller
{
    public function index(Request $request, int $vaultId, int $contactId): JsonResponse
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);
        $collection = ModuleCompanyViewHelper::list($vault, $contact);

        return response()->json([
            'data' => $collection,
        ], 200);
    }

    public function update(Request $request, int $vaultId, int $contactId)
    {
        if ($request->input('company_id')) {
            $company = Company::findOrFail($request->input('company_id'));
        }

        if ($request->input('company_name')) {
            $data = [
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::user()->id,
                'vault_id' => $vaultId,
                'name' => $request->input('company_name'),
                'type' => Company::TYPE_COMPANY,
            ];

            $company = (new CreateCompany)->execute($data);
        }

        (new UpdateJobInformation)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'company_id' => $company->id,
            'job_position' => $request->input('job_position'),
        ]);

        $contact = Contact::findOrFail($contactId);

        return response()->json([
            'data' => ModuleCompanyViewHelper::data($contact),
        ], 200);
    }
}
