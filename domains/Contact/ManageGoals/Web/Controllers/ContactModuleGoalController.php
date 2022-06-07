<?php

namespace App\Contact\ManageGoals\Web\Controllers;

use App\Contact\ManageGoals\Services\CreateGoal;
use App\Contact\ManageGoals\Services\DestroyGoal;
use App\Contact\ManageGoals\Services\UpdateGoal;
use App\Contact\ManageGoals\Web\ViewHelpers\ModuleGoalsViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleGoalController extends Controller
{
    public function store(Request $request, int $vaultId, int $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'name' => $request->input('name'),
        ];

        $goal = (new CreateGoal)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleGoalsViewHelper::dto($contact, $goal),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $contactId, int $goalId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'goal_id' => $goalId,
            'name' => $request->input('name'),
        ];

        $goal = (new UpdateGoal)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleGoalsViewHelper::dto($contact, $goal),
        ], 200);
    }

    public function destroy(Request $request, int $vaultId, int $contactId, int $goalId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'goal_id' => $goalId,
        ];

        (new DestroyGoal)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
