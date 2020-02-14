<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\InstanceHelper;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Services\Contact\Description\SetPersonalDescription;

class DescriptionController extends Controller
{
    /**
     * Assign a contact description to the given contact.
     *
     * @param Contact $contact
     * @return Response
     */
    public function create(Request $request, Contact $contact)
    {
        $request = [
            'account_id' => auth()->user()->account_id,
            'author_id' => auth()->user()->id,
            'contact_id' => $contact->id,
            'description' => $request->input('description'),
        ];

        $contact = (new SetPersonalDescription)->execute($request);

        return response()->json([
            'description' => $contact->description,
        ], 200);
    }

    /**
     * Remove the employee description for the given employee.
     *
     * @param int $companyId
     * @param int $contactId
     * @param int $employeeStatusId
     * @return Response
     */
    public function destroy(Request $request, int $companyId, int $contactId, int $employeeStatusId)
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $request = [
            'account_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $contactId,
        ];

        $employee = (new ClearPersonalDescription)->execute($request);

        return response()->json([
            'data' => $employee->toObject(),
        ], 200);
    }
}
