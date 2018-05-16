<?php

namespace App\Http\Controllers;

use App\User;
use App\InBoundEmail;
use App\ContactFieldType;
use Illuminate\Http\Request;

class InBoundEmailController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('webhook');
    }

    public function new_postmark(Request $request)
    {
        $from = $request->FromFull['Email'];

        $user = User::where('email', $from)->firstOrFail();

        $contacts = $user->account->contacts()->real();

        $email_contents = $request->TextBody;

        $email_data = [
          'from_email' => '',
          'to_email' => '',
          'subjct' => '',
          'datetime' => '',
        ];

        $pattern = '/From:.{1,}<(.{1,})>/m';
        if (preg_match($pattern, $email_contents, $matches)) {
            $email_data['from_email'] = $matches[1];
        } else {
            abort(400);
        }

        $pattern = '/To:.{1,}<(.{1,})>/m';
        if (preg_match($pattern, $email_contents, $matches)) {
            $email_data['to_email'] = $matches[1];
        } else {
            abort(400);
        }

        $pattern = '/Date:\s{1,}(.{1,})/m';
        if (preg_match($pattern, $email_contents, $matches)) {
            $email_data['datetime'] = $matches[1];
        } else {
            abort(400);
        }

        $pattern = '/Subject:\s{1,}(.{1,})/m';
        if (preg_match($pattern, $email_contents, $matches)) {
            $email_data['subject'] = $matches[1];
        } else {
            abort(400);
        }

        $field = ContactFieldType::where('name', 'Email')->first();

        $field_id = $field->id;

        $contacts = $contacts->whereHas('contactFields', function ($query) use ($field_id,$email_data) {
            $to_email = $email_data['to_email'];
            $from_email = $email_data['from_email'];

            $query->where([
                ['data', "$to_email"],
                ['contact_field_type_id', $field_id],
            ])->orWhere([
                ['data', "$from_email"],
                ['contact_field_type_id', $field_id],
            ]);
        })->get();

        $email_data['datetime'] = str_replace_first('at', '', $email_data['datetime']);

        $email = new InBoundEmail;
        $email->account_id = $user->account_id;
        $email->to = $email_data['to_email'];
        $email->from = $email_data['from_email'];
        $email->subject = $email_data['subject'];
        $email->sent = $email_data['datetime'];
        $email->content = $email_contents;
        $email->save();

        foreach ($contacts as $contact) {
            $email->setToContact($contact);
        }

        return response('OK', 200);
    }
}
