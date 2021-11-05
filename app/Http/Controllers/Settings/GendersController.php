<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Helpers\GenderHelper;
use App\Models\Contact\Gender;
use Illuminate\Validation\Rule;
use App\Helpers\CollectionHelper;
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
            $gendersData->push($this->formatData($gender));
        }

        return CollectionHelper::sortByCollator($gendersData, 'name');
    }

    /**
     * Get all the gender sex types.
     */
    public function types()
    {
        $gendersData = collect([]);

        $types = [
            Gender::MALE,
            Gender::FEMALE,
            Gender::OTHER,
            Gender::UNKNOWN,
            Gender::NONE,
        ];

        foreach ($types as $type) {
            $gendersData->push([
                'id' => $type,
                'name' => trans('settings.personalization_genders_'.strtolower($type)),
            ]);
        }

        return CollectionHelper::sortByCollator($gendersData, 'name');
    }

    /**
     * Store the gender.
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|max:255',
            'type' => ['required', Rule::in(Gender::LIST)],
        ])->validate();

        $gender = auth()->user()->account->genders()->create(
            $request->only([
                'name',
                'type',
            ])
            + [
                'account_id' => auth()->user()->account_id,
            ]
        );

        if ($request->input('isDefault')) {
            $this->updateDefault($gender);
        }

        return $this->formatData($gender);
    }

    /**
     * Update the given gender.
     */
    public function update(GendersRequest $request, Gender $gender)
    {
        $gender->update(
            $request->only([
                'name',
                'type',
            ])
        );
        if ($request->input('isDefault')) {
            $this->updateDefault($gender);
            $gender->refresh();
        } elseif ($gender->isDefault()) {
            // Case of this gender was the default one previously
            $account = auth()->user()->account;
            $account->default_gender_id = null;
            $account->save();
            $gender->refresh();
        }

        return $this->formatData($gender);
    }

    /**
     * Destroy a gender type.
     */
    public function destroyAndReplaceGender(Gender $gender, $genderId)
    {
        $account = auth()->user()->account;
        try {
            $genderToReplaceWith = Gender::where('account_id', $account->id)
                ->findOrFail($genderId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => trans('settings.personalization_genders_modal_error'),
            ], 403);
        }

        // We get the new gender to associate the contacts with.
        GenderHelper::replace($account, $gender, $genderToReplaceWith);

        if ($gender->isDefault()) {
            $account->default_gender_id = $genderToReplaceWith->id;
            $account->save();
        }

        $gender->delete();

        return $this->respondObjectDeleted($gender->id);
    }

    /**
     * Destroy a gender type.
     */
    public function destroy(Gender $gender)
    {
        $gender->delete();

        return $this->respondObjectDeleted($gender->id);
    }

    /**
     * Update the given gender to the default gender.
     */
    public function updateDefault(Gender $gender)
    {
        $account = auth()->user()->account;
        $account->default_gender_id = $gender->id;
        $account->save();

        return $this->formatData($gender);
    }

    /**
     * Format data for output.
     *
     * @param  Gender  $gender
     * @return array
     */
    private function formatData($gender)
    {
        return [
            'id' => $gender->id,
            'name' => $gender->name,
            'type' => $gender->type,
            'isDefault' => $gender->isDefault(),
            'numberOfContacts' => $gender->contacts->count(),
        ];
    }
}
