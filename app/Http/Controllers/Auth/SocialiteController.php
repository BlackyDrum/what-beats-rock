<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function github()
    {
        $githubUser = Socialite::driver('github')->user();

        $user = User::updateOrCreate([
            'email' => $githubUser->email,
        ], [
            'name' => $githubUser->nickname ?? $githubUser->name,
            'email' => $githubUser->email,
            'password' => Hash::make(Str::random()),
            'email_verified_at' => now()
        ]);

        Auth::login($user);

        return redirect('/');
    }

    public function google()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::updateOrCreate([
            'email' => $googleUser->email,
        ], [
            'name' => $googleUser->nickname ?? $googleUser->name,
            'email' => $googleUser->email,
            'password' => Hash::make(Str::random()),
            'email_verified_at' => now()
        ]);

        Auth::login($user);

        return redirect('/');
    }
}
