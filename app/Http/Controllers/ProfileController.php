<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\MitraProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ProfileController extends Controller
{
    public function index()
    {
        $user  = Auth::user();
        $mitra = MitraProfile::firstOrCreate(['user_id' => $user->id]);
        return view('profile.index', compact('user','mitra'));
    }

    public function update(UpdateProfileRequest $r)
{
    $user  = Auth::user();
    $mitra = MitraProfile::firstOrCreate(
        ['user_id' => $user->id],
        [
            // default kolom NOT NULL lain jika ada
            'alamat'         => null,
            'nama_bank'      => null,
            'nomor_rekening' => null,
            'nama_rekening'  => null,
        ]
    );

    // update akun (tabel users)
    $user->username = $r->username;
    $user->no_telp  = $r->phone;   // <<< SIMPAN di users.no_telp
    $user->save();

    // update profil non-bank (tabel mitra_profiles)
    $mitra->alamat = $r->address;    // <<< 'address' dari form, simpan ke kolom 'alamat'

    // isi data bank hanya jika masih kosong (lock setelah terisi)
    if (blank($mitra->nama_bank)      && $r->filled('nama_bank'))      $mitra->nama_bank      = $r->nama_bank;
    if (blank($mitra->nomor_rekening) && $r->filled('nomor_rekening')) $mitra->nomor_rekening = $r->nomor_rekening;
    if (blank($mitra->nama_rekening)  && $r->filled('nama_rekening'))  $mitra->nama_rekening  = $r->nama_rekening;

    $mitra->save();

    return back()->with('status','Profil berhasil diperbarui.');
}


    public function updatePhoto(Request $r)
    {
        $r->validate(['cropped_image' => 'required|string']);

    $user = Auth::user();
    $data = preg_replace('#^data:image/\w+;base64,#i', '', $r->cropped_image);
    $binary = base64_decode($data);

    // proses lagi dengan Intervention (orientasi+sharpen+encode)
    $img = \Intervention\Image\Facades\Image::make($binary)
            ->orientate()
            ->fit(128,128, function($c){ $c->upsize(); })
            ->sharpen(10)
            ->encode('webp', 85); // webp kecil & jernih

    $dir = 'profile-photos';
    $filename = 'profile-'.$user->id.'-'.time().'.webp';
    \Storage::disk('public')->put("$dir/$filename", (string) $img);

    // hapus lama (opsional)
    if ($user->photo && str_starts_with($user->photo, 'storage/')) {
        \Storage::disk('public')->delete(str_replace('storage/','',$user->photo));
    }

    $user->photo = 'storage/'.$dir.'/'.$filename;
    $user->save();

    return back()->with('status', 'Foto profil disimpan.');
        // $r->validate([
        //     'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        // ]);

        // $user = Auth::user();
        // $file = $r->file('photo');

        // // pilih format terbaik
        // $supportsWebp = function_exists('imagewebp');
        // $format  = $supportsWebp ? 'webp' : 'jpg';
        // $quality = 85;

        // // compress + resize 128x128, orientasi benar, sharpen
        // $img = Image::make($file->getRealPath())
        //     ->orientate()
        //     ->fit(128, 128, function ($c) { $c->upsize(); })
        //     ->sharpen(10)
        //     ->encode($format, $quality);

        // $dir = 'profile-photos';
        // $filename = 'profile-'.$user->id.'-'.time().'.'.$format;

        // Storage::disk('public')->put("$dir/$filename", (string) $img);

        // // hapus foto lama (jika ada)
        // if ($user->photo && str_starts_with($user->photo, 'storage/')) {
        //     Storage::disk('public')->delete(str_replace('storage/', '', $user->photo));
        // }

        // // simpan path baru
        // $user->photo = 'storage/'.$dir.'/'.$filename;
        // $user->save();

        // return back()->with('status','Foto profil berhasil diunggah & dikompres (128×128).');
    }

    public function updatePassword(UpdatePasswordRequest $r)
    {
        $user = Auth::user();

        if (! Hash::check($r->old_password, $user->password)) {
            return back()->withErrors(['old_password' => 'Password lama tidak sesuai.'])->withInput();
        }

        if (Hash::check($r->new_password, $user->password)) {
            return back()->withErrors(['new_password' => 'Password baru tidak boleh sama dengan password lama.'])->withInput();
        }

        $user->password = Hash::make($r->new_password);
        $user->save();

        return back()->with('status','Password berhasil diubah.');
    }
}
