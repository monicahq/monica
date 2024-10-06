<?php

namespace App\Domains\Contact\ManageGoals\Web\Controllers;

use App\Domains\Contact\ManageGoals\Services\DestroyGoal;
use App\Domains\Contact\ManageGoals\Services\UpdateGoal;
use App\Domains\Contact\ManageGoals\Web\ViewHelpers\GoalShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Goal;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContactGoalController extends Controller
{
    public function show(Request $request, string $vaultId, string $contactId, int $goalId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);
        $goal = Goal::where('contact_id', $contact->id)->findOrFail($goalId);

        return Inertia::render('Vault/Contact/Goals/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => GoalShowViewHelper::data($contact, Auth::user(), $goal),
        ]);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $goalId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'goal_id' => $goalId,
            'name' => $request->input('name'),
        ];

        $contact = Contact::findOrFail($contactId);
        $goal = Goal::where('contact_id', $contact->id)->findOrFail($goalId);

        $goal = (new UpdateGoal)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => GoalShowViewHelper::data($contact, Auth::user(), $goal),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $goalId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'goal_id' => $goalId,
        ];

        $contact = Contact::findOrFail($contactId);
        Goal::where('contact_id', $contact->id)->findOrFail($goalId);

        (new DestroyGoal)->execute($data);

        return response()->json([
            'data' => route('contact.show', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]),
        ], 200);
    }
}
