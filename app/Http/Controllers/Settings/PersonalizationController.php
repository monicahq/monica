<?php

namespace App\Http\Controllers\Settings;

use Validator;
use App\Gender;
use App\ContactFieldType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\GendersRequest;

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

        $contactFieldType = auth()->user()->account->contactFieldTypes()->create(
            $request->only([
                'name',
                'protocol',
            ])
            + [
                'fontawesome_icon' => $request->get('icon'),
                'account_id' => auth()->user()->account->id,
            ]
        );

        return $contactFieldType;
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

        if ($contactFieldType->delible == false) {
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

    /**
     * Get all the gender types.
     */
    public function getGenderTypes()
    {
        $gendersData = collect([]);
        $genders = auth()->user()->account->genders;

        foreach ($genders as $gender) {
            $data = [
                'id' => $gender->id,
                'name' => $gender->name,
                'numberOfContacts' => $gender->contacts->count(),
            ];
            $gendersData->push($data);
        }

        return $gendersData;
    }

    /**
     * Store the gender.
     */
    public function storeGender(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        $gender = auth()->user()->account->genders()->create(
            $request->only([
                'name',
            ])
            + [
                'account_id' => auth()->user()->account->id,
            ]
        );

        return $gender;
    }

    /**
     * Update the given gender.
     */
    public function updateGender(Request $request, $genderId)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        try {
            $gender = Gender::where('account_id', auth()->user()->account_id)
                ->where('id', $genderId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'errors' => [
                    'message' => trans('app.error_unauthorized'),
                ],
            ]);
        }

        $gender->update(
            $request->only([
                'name',
            ])
        );

        return $gender;
    }

    /**
     * Destroy a gender type.
     */
    public function destroyGender(GendersRequest $request, Gender $gender)
    {
        dd($request->input('newName'));
        try {
            $gender = Gender::where('account_id', auth()->user()->account_id)
                ->where('id', $genderId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'errors' => [
                    'message' => trans('app.error_unauthorized'),
                ],
            ]);
        }

        // Does the gender have contacts associated with it?
        // If yes, we need to have a new gender associated with them, otherwise
        // we raise an error.
        $numberOfContacts = $gender->contacts->count();
        if ($numberOfContacts > 0 && $request->input('newName') == '') {
            return response()->json([
                'errors' => [
                    'message' => trans('app.error_unauthorized'),
                ],
            ]);
        }

        if ($numberOfContacts > 0 && $request->input('newName') != '') {
            // We get the new gender to associate the contacts with.
            $genderToAssociate = Gender::where('account_id', auth()->user()->account_id)
                                    ->where('name', $request->input('newName'))
                                    ->firstOrFail();

            App\Contact::where('account_id', auth()->user()->account->id)
                        ->where('gender_id', $genderId)
                        ->update(['gender_id' => $genderToAssociate->id]);
        }

        $gender->delete();
    }
}
