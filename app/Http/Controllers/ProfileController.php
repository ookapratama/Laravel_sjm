<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'username' => 'required|unique:users,username,' . $user->id,
            'name' => 'required|string|max:255',
            'password' => 'nullable|confirmed|min:6',
            'email' => 'nullable|email',
            'no_telp' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'bank_account' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
        ]);

        $user->username = $request->username;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->no_telp = $request->phone;
        $user->address = $request->address;
        $user->bank_account = $request->bank_account;
        $user->bank_name = $request->bank_name;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
