<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback($provider): \Illuminate\Http\RedirectResponse
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = User::updateOrCreate([
            'email' => $socialUser->getEmail(),
        ], [
            'name' => $socialUser->getName(),
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return redirect()->away("http://localhost:8080/oauth-success?token={$token}");
    }

    public function user(Request $request)
    {
        return response()->json(Auth::user());
    }

    public function logout(Request $request){
        Auth::user()->tokens()->delete();
        return [
            'message' => 'user logged out'
        ];
    }

}
