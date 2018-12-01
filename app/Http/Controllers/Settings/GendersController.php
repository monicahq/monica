<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Models\Contact\Gender;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Settings\GendersRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GendersController extends Controller
{
    use JsonRespondController;

    /**
     * Get all the gender types.
     */
    public function index()
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
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
        ])->validate();

        $gender = auth()->user()->account->genders()->create(
            $request->only([
                'name',
            ])
            + [
                'account_id' => auth()->user()->account_id,
            ]
        );

        return [
            'id' => $gender->id,
            'name' => $gender->name,
            'numberOfContacts' => $gender->contacts->count(),
        ];
    }

    /**
     * Update the given gender.
     */
    public function update(GendersRequest $request, Gender $gender)
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
    public function destroyAndReplaceGender(GendersRequest $request, Gender $gender, $genderId)
    {
        try {
            $genderToReplaceWith = Gender::where('account_id', auth()->user()->account_id)
                ->findOrFail($genderId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => trans('settings.personalization_genders_modal_error'),
            ], 403);
        }

        // We get the new gender to associate the contacts with.
        auth()->user()->account->replaceGender($gender, $genderToReplaceWith);

        $gender->delete();

        return $this->respondObjectDeleted($gender->id);
    }

    /**
     * Destroy a gender type.
     */
    public function destroy(GendersRequest $request, Gender $gender)
    {
        $gender->delete();

        return $this->respondObjectDeleted($gender->id);
    }
}
