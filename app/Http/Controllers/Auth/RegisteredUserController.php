<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
 {
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'username' => ['required', 'string', 'max:255','unique:users'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'confirmed', 'min:3'],
    ]);

    $sponsor_id = $request->input('sponsor_id');
    $upline_id = null;
    $position = null;

    if ($sponsor_id) {
        $sponsor = User::find($sponsor_id);
        if (!$sponsor) {
            return back()->withErrors(['sponsor_id' => 'Sponsor tidak ditemukan.']);
        }

        // Cari kaki kosong
        $left = User::where('upline_id', $sponsor->id)->where('position', 'left')->first();
        $right = User::where('upline_id', $sponsor->id)->where('position', 'right')->first();

        if (!$left) {
            $upline_id = $sponsor->id;
            $position = 'left';
        } elseif (!$right) {
            $upline_id = $sponsor->id;
            $position = 'right';
        } else {
            return back()->withErrors(['sponsor_id' => 'Sponsor sudah penuh (dua kaki)']);
        }
    }

    $user = User::create([
        'name' => $request->name,
       'username' => $request->username,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => 'member',
        'sponsor_id' => $sponsor_id,
        'upline_id' => $upline_id,
        'position' => $position,
        'joined_at' => now(),
    ]);

    Auth::login($user);

    return redirect(RouteServiceProvider::HOME);
}
}
