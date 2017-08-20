<?php

namespace App\Http\Controllers\Auth;

use App\Traits\CreateAccount;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use HumanNameParser\Parser;
use Socialite;

class SocialLoginController extends Controller
{
    use CreateAccount;

    public function __construct()
    {
        $this->middleware(['social', 'guest']);
    }
    
    public function redirect($service, Request $request)
    {
        return Socialite::driver($service)->redirect();
    }

    public function callback($service, Request $request)
    {
        $serviceUser = Socialite::driver($service)->user();

        $user = $this->getExistingUser($serviceUser, $service);

        if(!$user){
            $nameparser = new Parser();
            $parsedName = $nameparser->parse($serviceUser->getName());

            $user = $this->createAccount([
                'first_name' => $parsedName->getFirstName(),
                'last_name' => $parsedName->getLastName(),
                'email' => $serviceUser->getEmail(),
            ]);
        }
        if ($this->needsToCreateSocial($user, $service))
        {
            $user->social()->create([
                'social_id' => $serviceUser->getId(),
                'service' => $service,
                'access_token' => $serviceUser->token,
            ]);

        } else {
            $us = $user->social()->where('service', $service)->get()->first();
            $us->access_token = $serviceUser->token;
            $us->save();
        }

        Auth::login($user, TRUE);
        return redirect()->intended();
    }

    public function needsToCreateSocial(User $user, $service)
    {
        return !$user->hasSocialAccountLinked($service);
    }

    protected function getExistingUser($serviceUser, $service)
    {
        return User::where('email', $serviceUser->getEmail())->orWhereHas('social', function ($q) use ($serviceUser, $service){
           $q->where('social_id', $serviceUser->getId())->where('service', $service);
        })->first();
    }
}
