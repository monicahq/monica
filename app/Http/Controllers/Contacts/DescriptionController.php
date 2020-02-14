<?php

namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Contact;
use Illuminate\Http\Request;
use App\Helpers\InstanceHelper;
use App\Http\Controllers\Controller;
use App\Services\Contact\Description\ClearPersonalDescription;
use App\Services\Contact\Description\SetPersonalDescription;
use Illuminate\Http\Response;

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
     * @param Contact $contact
     * @return Response
     */
    public function destroy(Request $request, Contact $contact)
    {
        $request = [
            'account_id' => auth()->user()->account_id,
            'author_id' => auth()->user()->id,
            'contact_id' => $contact->id,
        ];

        $employee = (new ClearPersonalDescription)->execute($request);

        return response()->json([
            'description' => null,
        ], 200);
    }
}
