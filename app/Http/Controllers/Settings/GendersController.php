<?php

namespace App\Http\Controllers\Settings;

use Validator;
use App\Gender;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\GendersRequest;

class GendersController extends Controller
{
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
    public function destroyGender(GendersRequest $request, Gender $gender, Gender $otherGender)
    {
        dd($otherGender->name);
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
