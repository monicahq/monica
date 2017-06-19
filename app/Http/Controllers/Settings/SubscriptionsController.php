<?php

namespace App\Http\Controllers\Settings;

use App\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (config('monica.unlock_paid_features')) {
            return redirect('settings/');
        }
dd(auth()->user()->account->subscribed(config('monica.paid_plan_friendly_name')));
        if (auth()->user()->subscribed(config('monica.paid_plan_friendly_name'))) {
            return view('settings.subscriptions.account');
        }

        return view('settings.subscriptions.index');
    }

    /**
     * Show the upgrade page.
     *
     * @return \Illuminate\Http\Response
     */
    public function upgrade()
    {
        if (config('monica.unlock_paid_features')) {
            return redirect('settings/');
        }

        return view('settings.subscriptions.upgrade');
    }

    public function processPayment(Request $request)
    {
        if (config('monica.unlock_paid_features')) {
            return redirect('settings/');
        }

        $stripeToken = $request->input('stripeToken');

        auth()->user()->account->newSubscription(config('monica.paid_plan_friendly_name'), config('monica.paid_plan_id'))
                    ->create($stripeToken);

        return 'ok';
    }
}
