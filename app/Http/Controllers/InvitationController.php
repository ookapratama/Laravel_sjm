<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index(Request $r)
    {
        $list = Invitation::where('created_by', $r->user()->id)
                ->latest('id')->paginate(12);
        return view('inv.index', ['invitations' => $list]);
    }

    public function create()
    {
        return view('inv.form', ['invitation' => new Invitation()]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'title'          => 'required|string|max:150',
            'description'    => 'nullable|string|max:2000',
            'event_datetime' => 'nullable|date',
            'venue_name'     => 'nullable|string|max:150',
            'venue_address'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
          'theme' => 'nullable|string|in:luxury,royal_marble,baroque',
            'primary_color'  => 'nullable|string|max:20',
            'secondary_color'=> 'nullable|string|max:20',
            'background'     => 'nullable|image|max:4096',
        ]);

        $slug = Str::slug($data['title']).'-'.Str::random(6);
        $bgPath = $r->file('background')? $r->file('background')->store('inv_banners','public') : null;

        $inv = Invitation::create([
            'created_by'      => $r->user()->id,
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'event_datetime'  => $data['event_datetime'] ?? null,
            'venue_name'      => $data['venue_name'] ?? null,
            'venue_address'   => $data['venue_address'] ?? null,
            'city'            => $data['city'] ?? null,
            'theme'           => $data['theme'] ?? 'luxury',
            'primary_color'   => $data['primary_color'] ?? null,
            'secondary_color' => $data['secondary_color'] ?? null,
            'background_image'=> $bgPath,
            'slug'            => $slug,
            'is_active'       => true,
        ]);

        return redirect()->route('inv.qr', $inv)->with('ok','Undangan dibuat.');
    }

    public function edit(Invitation $invitation)
    {
        // opsional
        return view('inv/form', compact('invitation'));
    }

    public function update(Request $r, Invitation $invitation)
    {

        $data = $r->validate([
            'title'          => 'required|string|max:150',
            'description'    => 'nullable|string|max:2000',
            'event_datetime' => 'nullable|date',
            'venue_name'     => 'nullable|string|max:150',
            'venue_address'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'theme' => 'nullable|string|in:luxury,royal_marble,baroque',
            'primary_color'  => 'nullable|string|max:20',
            'secondary_color'=> 'nullable|string|max:20',
            'background'     => 'nullable|image|max:4096',
            'is_active'      => 'sometimes|boolean',
        ]);

        if ($r->hasFile('background')) {
            if ($invitation->background_image) {
                Storage::disk('public')->delete($invitation->background_image);
            }
            $invitation->background_image = $r->file('background')->store('inv_banners','public');
        }

        $invitation->fill($data)->save();
        return back()->with('ok','Undangan diperbarui.');
    }

    // halaman QR share (pakai ReferralQrController untuk gambar QR)
    public function qr(Request $r, Invitation $invitation)
    {
       // $this->authorize('view', $invitation);
        $ref = $r->user()->referral_code ?? $r->user()->username ?? 'REF'.str_pad($r->user()->id,4,'0',STR_PAD_LEFT);
        $publicUrl = route('inv.public', $invitation->slug);
        $formUrl   = route('guest.form.inv', $invitation->slug).'?ref='.$ref.'&src=INV';
        return view('inv/qr', compact('invitation','publicUrl','formUrl','ref'));
    }

    // landing publik
    // use App\Models\User;  // (opsional jika ingin validasi ref)

public function publicShow(Request $r, string $slug)
{
    $inv = Invitation::where('slug',$slug)->where('is_active',true)->firstOrFail();

    $ref = $r->query('ref'); // bisa dari ?ref=...
    // (opsional) validasi ref jika perlu:
    // if ($ref && !User::where('referral_code',$ref)->exists()) { $ref = null; }

    $response = response()->view('inv/public/show', [
        'invitation' => $inv,
        'ref'        => $ref,
    ]);

    // simpan ref 7 hari (per undangan), agar tombol tetap autofill meski user balik lagi
    if ($ref) {
        $response->cookie('inv_ref_'.$slug, $ref, 60*24*7); // minutes
    }

    return $response;
}

}
