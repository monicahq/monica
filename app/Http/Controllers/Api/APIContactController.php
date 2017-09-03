<?php

namespace App\Http\Controllers\Api;

use App\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class APIContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        dd($request->user());
        //$contacts = Contact::paginate(3);
        $contacts = Contact::all();

        dd(auth()->user()->id);
        //$user->account->contacts()->real();

        $response = [
          'data' => $contacts->toArray(),
        ];

        return response()->json($response, 200);
    }
}
