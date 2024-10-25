<?php

namespace App\Domains\Contact\ManageQuickFacts\Web\Controllers;

use App\Domains\Contact\ManageQuickFacts\Services\CreateQuickFact;
use App\Domains\Contact\ManageQuickFacts\Services\DestroyQuickFact;
use App\Domains\Contact\ManageQuickFacts\Services\UpdateQuickFact;
use App\Domains\Contact\ManageQuickFacts\Web\ViewHelpers\ContactModuleQuickFactViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactQuickFactController extends Controller
{
    public function show(Request $request, string $vaultId, string $contactId, int $templateId): JsonResponse
    {
        $contact = Contact::find($contactId);
        $template = $contact->vault->quickFactsTemplateEntries()->findOrFail($templateId);

        return response()->json([
            'data' => ContactModuleQuickFactViewHelper::data($contact, $template),
        ], 200);
    }

    public function store(Request $request, string $vaultId, string $contactId, int $templateId): JsonResponse
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'vault_quick_facts_template_id' => $templateId,
            'content' => $request->input('content'),
        ];

        $quickFact = (new CreateQuickFact)->execute($data);

        return response()->json([
            'data' => ContactModuleQuickFactViewHelper::dto($quickFact),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $templateId, int $quickFactId): JsonResponse
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'quick_fact_id' => $quickFactId,
            'content' => $request->input('content'),
        ];

        $quickFact = (new UpdateQuickFact)->execute($data);

        return response()->json([
            'data' => ContactModuleQuickFactViewHelper::dto($quickFact),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $templateId, int $quickFactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'quick_fact_id' => $quickFactId,
        ];

        (new DestroyQuickFact)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
