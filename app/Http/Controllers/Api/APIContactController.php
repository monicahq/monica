<?php

namespace App\Http\Controllers\Api;

use App\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiContactController extends ApiController
{
    /**
     * Get the list of the contacts.
     * We will only retrieve the contacts that are "real", not the partials
     * ones.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $contacts = auth()->user()->account->contacts()->real()
                                            ->paginate($this->getLimitPerPage());

        $response = [
          'data' => $contacts->toArray(),
        ];

        return $this->respond($response);
    }
}
