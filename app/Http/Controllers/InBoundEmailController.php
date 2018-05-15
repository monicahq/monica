<?php

namespace App\Http\Controllers;

use App\User;
use App\InBoundEmail;
use App\Account;
use App\Contacts;
use App\ContactFieldType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class InBoundEmailController extends Controller
{

    public function new_postmark(Request $request) {

        $from = $request->FromFull['Email'];

        $user = User::where('email', $from)->first();

        $contacts = $user->account->contacts()->real();

        $email_contents = $request->TextBody;

        $pattern = '/From:.{1,}<(.{1,})>/m';
        if(preg_match($pattern, $email_contents, $matches)){
            $from_email = $matches[1];
        } else {
            abort(400);
        }

        $pattern = '/To:.{1,}<(.{1,})>/m';
        if(preg_match($pattern, $email_contents, $matches)){
            $to_email = $matches[1];
        } else {
            abort(400);
        }

        $pattern = '/Date:\s{1,}(.{1,})/m';
        if(preg_match($pattern, $email_contents, $matches)){
            $datetime_email = $matches[1];
        } else {
            abort(400);
        }

        $pattern = '/Subject:\s{1,}(.{1,})/m';
        if(preg_match($pattern, $email_contents, $matches)){
            $subject_email = $matches[1];
        } else {
            abort(400);
        }

        $field = ContactFieldType::where('name', 'Email')->first();

        $field_id = $field->id;

        $contacts = $contacts->whereHas('contactFields', function ($query) use ($field_id,$to_email,$from_email) {
            $query->where([
                ['data', "$to_email"],
                ['contact_field_type_id', $field_id],
            ])->orWhere([
                ['data', "$from_email"],
                ['contact_field_type_id', $field_id],
            ]);
        })->get();

        $datetime_email = str_replace_first('at', '', $datetime_email);

        $email = new InBoundEmail;
        $email->account_id = $user->account_id;
        $email->to = $to_email;
        $email->from = $from_email;
        $email->subject = $subject_email;
        $email->sent = $datetime_email;
        $email->content = $email_contents;
        $email->save();

        foreach ($contacts as $contact) {
            $email->setToContact($contact);
        }

        return response('OK', 200);
    }
}
