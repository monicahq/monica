<?php

namespace App\Http\Controllers\Settings;

use App\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\GendersRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
                'id'               => $gender->id,
                'name'             => $gender->name,
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

        return [
            'id'               => $gender->id,
            'name'             => $gender->name,
            'numberOfContacts' => $gender->contacts->count(),
        ];
    }

    /**
     * Update the given gender.
     */
    public function updateGender(GendersRequest $request, Gender $gender)
    {
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
    public function destroyAndReplaceGender(GendersRequest $request, Gender $genderToDelete, $genderToReplaceWithId)
    {
        try {
            $genderToReplaceWith = Gender::where('account_id', auth()->user()->account_id)
                ->where('id', $genderToReplaceWithId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new Exception(trans('settings.personalization_genders_modal_error'));
        }

        // We get the new gender to associate the contacts with.
        auth()->user()->account->replaceGender($genderToDelete, $genderToReplaceWith);

        $genderToDelete->delete();
    }

    /**
     * Destroy a gender type.
     */
    public function destroyGender(GendersRequest $request, Gender $gender)
    {
        $gender->delete();
    }
}
