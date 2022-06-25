<?php

namespace App\Contact\ManageGoals\Web\Controllers;

use App\Contact\ManageGoals\Services\ToggleStreak;
use App\Contact\ManageGoals\Web\ViewHelpers\ModuleGoalsViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleStreakController extends Controller
{
    public function update(Request $request, int $vaultId, int $contactId, int $goalId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'goal_id' => $goalId,
            'happened_at' => $request->input('happened_at'),
        ];

        (new ToggleStreak())->execute($data);

        $contact = Contact::find($contactId);
        $goal = Goal::find($goalId);

        return response()->json([
            'data' => ModuleGoalsViewHelper::dto($contact, $goal),
        ], 200);
    }
}
