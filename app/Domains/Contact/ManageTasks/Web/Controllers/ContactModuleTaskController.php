<?php

namespace App\Domains\Contact\ManageTasks\Web\Controllers;

use App\Domains\Contact\ManageTasks\Services\CreateContactTask;
use App\Domains\Contact\ManageTasks\Services\DestroyContactTask;
use App\Domains\Contact\ManageTasks\Services\ToggleContactTask;
use App\Domains\Contact\ManageTasks\Services\UpdateContactTask;
use App\Domains\Contact\ManageTasks\Web\ViewHelpers\ModuleContactTasksViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleTaskController extends Controller
{
    public function index(Request $request, string $vaultId, string $contactId)
    {
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactTasksViewHelper::completed($contact, Auth::user()),
        ], 200);
    }

    public function store(Request $request, string $vaultId, string $contactId)
    {
        $dueAt = '';
        if ($request->input('due_at')) {
            $dueAt = Carbon::parse($request->input('due_at'))->format('Y-m-d');
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'label' => $request->input('label'),
            'description' => null,
            'due_at' => $dueAt,
        ];

        $task = (new CreateContactTask)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactTasksViewHelper::dtoTask($contact, $task, Auth::user()),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $taskId)
    {
        $dueAt = '';
        if ($request->input('due_at_checked')) {
            $dueAt = Carbon::parse($request->input('due_at'))->format('Y-m-d');
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_task_id' => $taskId,
            'label' => $request->input('label'),
            'description' => null,
            'due_at' => $dueAt === '' ? null : $dueAt,
        ];

        $task = (new UpdateContactTask)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactTasksViewHelper::dtoTask($contact, $task, Auth::user()),
        ], 200);
    }

    public function toggle(Request $request, string $vaultId, string $contactId, int $taskId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_task_id' => $taskId,
            'label' => $request->input('label'),
            'description' => null,
        ];

        $task = (new ToggleContactTask)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactTasksViewHelper::dtoTask($contact, $task, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $taskId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'contact_task_id' => $taskId,
        ];

        (new DestroyContactTask)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
