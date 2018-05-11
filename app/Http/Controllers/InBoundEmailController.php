<?php

namespace App\Http\Controllers;

use App\User;
use App\Account;
use App\Contacts;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class InBoundEmailController extends Controller
{

    public function new(Request $request) {

        $from = $request->FromFull['Email'];

        $user = User::where('email', $from)->first();

        $account = $user->account();

        $contacts = $account->contacts();

        $email_contents = $request->TextBody;

        return response(200);

    }
}
