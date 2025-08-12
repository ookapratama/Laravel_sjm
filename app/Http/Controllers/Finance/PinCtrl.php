<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PinRequest;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PinCtrl extends Controller
{
    public function index(){
        return view('finance.pin_request', [
            'list'=> PinRequest::with('requester')->latest()->get()
        ]);
    }


public function approve(Request $r, $id)
{
    $data = $r->validate([
        'payment_method'    => 'required|string|max:50',
        'payment_reference' => 'required|string|max:100',
        'finance_notes'     => 'nullable|string|max:500',
    ]);

    DB::transaction(function () use ($id, $data) {
        // Kunci row agar aman dari race condition
        $req = PinRequest::with('requester')
            ->lockForUpdate()
            ->findOrFail($id);

        if ($req->status !== 'requested') {
            throw ValidationException::withMessages([
                'status' => 'Status tidak valid untuk approve.'
            ]);
        }

        // Hitung amount dari data permintaan
        $amount = $req->total_price ?? ($req->unit_price * $req->qty);

        // Update status PinRequest
        $req->update([
            'status'            => 'finance_approved',
            'payment_method'    => $data['payment_method'],
            'payment_reference' => $data['payment_reference'],
            'finance_notes'     => $data['finance_notes'] ?? null,
            'finance_id'        => auth()->id(),
            'finance_at'        => now(),
        ]);

        // === Catat Cash Transaction (idempotent) ===
        $already = CashTransaction::where('source', 'pin_purchase')
            ->where('payment_reference', $data['payment_reference'])
            ->exists();

        if (! $already) {
            CashTransaction::create([
                'user_id'           => $req->requester_id,
                'type'              => 'in',                 // uang masuk
                'source'            => 'pin_purchase',       // bebas, konsistenkan
                'amount'            => $amount,
                'notes'             => "Pembelian PIN #{$req->id} ({$req->qty} x " . number_format($req->unit_price) . ")",
                'payment_channel'   => $data['payment_method'],
                'payment_reference' => $data['payment_reference'],
            ]);
        }
    });

    return back()->with('success', 'Approved. Menunggu Admin generate.');
}

    public function reject(Request $r, $id){
        $notes = $r->validate(['finance_notes'=>'required|string|max:500'])['finance_notes'];
        $req = PinRequest::findOrFail($id);
        if ($req->status !== 'requested') return back()->with('error','Invalid.');
        $req->update([
            'status'     => 'finance_rejected',
            'finance_notes' => $notes,
            'finance_id' => auth()->id(),
            'finance_at' => now()
        ]);
        return back()->with('success','Ditolak.');
    }
}
