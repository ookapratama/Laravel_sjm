<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivationPin;   // <-- tambahkan
use App\Models\PinRequest;      // <-- tambahkan

class PinCtrl extends Controller
{
   public function index() 
    {
        abort_unless(auth()->user()->is_active, 403);

        $requests = PinRequest::where('requester_id', auth()->id())
            ->latest()
            ->get();

        $pins = ActivationPin::where('purchased_by', auth()->id())
            ->oldest('status')
            ->get();

        $hasOpen = $requests->contains(fn($r) => in_array($r->status, ['requested', 'finance_approved']));

        // Pastikan nama view sesuai file kamu: 'member.pins' atau 'member.pin'
        return view('member.pin', [
            'requests' => $requests,
            'pins'     => $pins,
            'hasOpen'  => $hasOpen,
        ]);
    }

    public function store(Request $r){
        abort_unless(auth()->user()->is_active, 403);
        $qty = (int) $r->validate(['qty'=>'required|integer|min:1|max:100'])['qty'];
        $unit = 750000;

        if (PinRequest::where('requester_id',auth()->id())->open()->exists()) {
            return back()->with('error','Masih ada request berjalan.');
        }
$path = null;
if ($r->hasFile('payment_proof')) {
    $path = $r->file('payment_proof')->store('payment-proofs/pin', 'public');
}


        PinRequest::create([
            'requester_id'=>auth()->id(),
            'qty'=>$qty, 
            'unit_price'=>$unit, 
            'total_price'=>$unit*$qty, 
            'status'=>'requested',
            'payment_method'=>$r->payment_method,
            'payment_reference'=>$r->payment_reference ?? null,
            'payment_proof'=> $path,
        ]);

        return back()->with('success','Request dikirim. Menunggu verifikasi Finance.');
    }
}

