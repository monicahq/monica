<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ValidateContactRequests
{
    /**
     * @param Request $request
     * @param array $rules
     * @return bool
     */
    public function validateUpdate(
        Request $request,
        array $rules
    ) {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return true;
    }
}
