<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Settings\ManageUsers\Services\AcceptInvitation;
use App\Http\Controllers\Auth\ViewHelpers\AcceptInvitationShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AcceptInvitationController extends Controller
{
    public function show(Request $request, string $code)
    {
        try {
            User::where('invitation_code', $code)
                ->whereNull('invitation_accepted_at')
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            return redirect('/');
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'data' => AcceptInvitationShowViewHelper::data($code),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'invitation_code' => 'required|uuid',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Password::min(8)->uncompromised()],
        ]);

        $data = [
            'invitation_code' => $request->input('invitation_code'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'password' => $request->input('password'),
        ];

        $user = (new AcceptInvitation)->execute($data);

        Auth::login($user);

        return response()->json([
            'data' => route('home'),
        ], 200);
    }
}
