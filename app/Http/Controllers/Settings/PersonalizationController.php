<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Helpers\AccountHelper;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Models\Contact\ContactFieldType;
use Illuminate\Support\Facades\Validator;

class PersonalizationController extends Controller
{
    use JsonRespondController;

    /**
     * Display the personalization page.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $accountHasLimitations = AccountHelper::hasLimitations(auth()->user()->account);

        return view('settings.personalization.index')
            ->withAccountHasLimitations($accountHasLimitations);
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
                'fontawesome_icon' => $request->input('icon'),
                'account_id' => auth()->user()->account_id,
            ]
        );
    }

    /**
     * Edit a newly created resource in storage.
     *
     * @param Request $request
     * @param ContactFieldType $contactFieldType
     *
     * @return ContactFieldType
     */
    public function editContactFieldType(Request $request, ContactFieldType $contactFieldType): ContactFieldType
    {
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
                'fontawesome_icon' => $request->input('icon'),
            ]
        );

        return $contactFieldType;
    }

    /**
     * Destroy the contact field type.
     */
    public function destroyContactFieldType(Request $request, ContactFieldType $contactFieldType)
    {
        if (! $contactFieldType->delible) {
            return $this->respondUnauthorized();
        }

        // find all the contact fields that have this contact field types
        $contactFields = auth()->user()->account->contactFields
                                ->where('contact_field_type_id', $contactFieldType->id);

        foreach ($contactFields as $contactField) {
            $contactField->delete();
        }

        $contactFieldType->delete();

        return $this->respondObjectDeleted($contactFieldType->id);
    }
}
