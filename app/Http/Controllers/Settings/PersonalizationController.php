<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Contact\ContactFieldType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PersonalizationController extends Controller
{
    /**
     * Display the personalization page.
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.personalization.index');
    }

    /**
     * Get all the contact field types.
     */
    public function getContactFieldTypes()
    {
        return auth()->user()->account->contactFieldTypes;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return string
     */
    public function storeContactFieldType(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
            'icon' => 'max:255|nullable',
            'protocol' => 'max:255|nullable',
        ])->validate();

        return auth()->user()->account->contactFieldTypes()->create(
            $request->only([
                'name',
                'protocol',
            ])
            + [
                'fontawesome_icon' => $request->get('icon'),
                'account_id' => auth()->user()->account->id,
            ]
        );
    }

    /**
     * Edit a newly created resource in storage.
     *
     * @param ContactFieldTypeRequest $request
     * @param string $contactFieldTypeId
     * @return \Illuminate\Http\Response
     */
    public function editContactFieldType(Request $request, $contactFieldTypeId)
    {
        try {
            $contactFieldType = ContactFieldType::where('account_id', auth()->user()->account_id)
                ->where('id', $contactFieldTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'errors' => [
                    'message' => trans('app.error_unauthorized'),
                ],
            ]);
        }

        Validator::make($request->all(), [
            'name' => 'required|max:255',
            'icon' => 'max:255|nullable',
            'protocol' => 'max:255|nullable',
        ])->validate();

        $contactFieldType->update(
            $request->only([
                'name',
                'protocol',
            ])
            + [
                'fontawesome_icon' => $request->get('icon'),
            ]
        );

        return $contactFieldType;
    }

    /**
     * Destroy the contact field type.
     */
    public function destroyContactFieldType(Request $request, $contactFieldTypeId)
    {
        try {
            $contactFieldType = ContactFieldType::where('account_id', auth()->user()->account_id)
                ->where('id', $contactFieldTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'errors' => [
                    'message' => trans('app.error_unauthorized'),
                ],
            ]);
        }

        if (! $contactFieldType->delible) {
            return response()->json([
                'errors' => [
                    'message' => trans('app.error_unauthorized'),
                ],
            ]);
        }

        // find all the contact fields that have this contact field types
        $contactFields = auth()->user()->account->contactFields
                                ->where('contact_field_type_id', $contactFieldTypeId);

        foreach ($contactFields as $contactField) {
            $contactField->delete();
        }

        $contactFieldType->delete();
    }
}
