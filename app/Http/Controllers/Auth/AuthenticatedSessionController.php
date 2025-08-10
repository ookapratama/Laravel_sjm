<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
public function store(Request $request)
{
    $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt([
        'username' => $request->username,
        'password' => $request->password,
    ], $request->boolean('remember'))) {

        $request->session()->regenerate();
        $user = Auth::user();

        // Cek wajib ganti username dan password
        if ($user->must_change_credentials) {
            return redirect()->route('change.credentials');
        }

        // Redirect berdasarkan role
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin');
            case 'super-admin':
                return redirect()->route('super-admin');
            case 'member':
                return redirect()->route('member');
            case 'finance':
                return redirect()->route('finance');
            default:
                return redirect()->route('dashboard');
        }
    }

    return back()->withErrors([
        'username' => 'Username atau password salah.',
    ]);
}


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
