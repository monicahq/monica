<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\Contact\Contact\SetMeContact;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\DeleteMeContact;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiMeController extends ApiController
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('limitations')->only('store');
        parent::__construct();
    }

    /**
     * Set a contact as 'me'.
     *
     * @param  Request  $request
     * @return string
     */
    public function store(Request $request)
    {
        $data = [
            'contact_id' => $request->input('contact_id'),
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->user()->id,
        ];

        try {
            app(SetMeContact::class)->execute($data);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return $this->respond(['true']);
    }

    /**
     * Removes contact as 'me' association.
     *
     * @param  Request  $request
     * @return string
     */
    public function destroy(Request $request)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'user_id' => auth()->user()->id,
        ];

        app(DeleteMeContact::class)->execute($data);

        return $this->respond(['true']);
    }
}
