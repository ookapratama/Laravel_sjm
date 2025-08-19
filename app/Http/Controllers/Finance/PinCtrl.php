<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PinRequest;
use App\Models\CashTransaction;
use App\Models\ActivationPin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;         
use GuzzleHttp\Client; 
use Illuminate\Http\Request;

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

    $req = null;
    $newCodes = collect();

    DB::transaction(function () use ($id, $data, &$req, &$newCodes) {
        // Kunci row
        $req = PinRequest::with('requester')
            ->lockForUpdate()
            ->findOrFail($id);

        if (!in_array($req->status, ['requested','finance_approved','generated'])) {
            throw ValidationException::withMessages(['status' => 'Status tidak valid untuk approve.']);
        }

        // Hitung amount
        $amount = $req->total_price ?? ($req->unit_price * $req->qty);

        // Update status & info finance
        $req->fill([
            'payment_method'    => $data['payment_method'],
            'payment_reference' => $data['payment_reference'],
            'finance_notes'     => $data['finance_notes'] ?? null,
            'finance_id'        => auth()->id(),
            'finance_at'        => now(),
        ])->save();

        // Catat Cash Transaction (idempotent by payment_reference)
        $already = CashTransaction::where('source','pin_purchase')
            ->where('payment_reference', $data['payment_reference'])
            ->exists();

        if (! $already) {
            CashTransaction::create([
                'user_id'           => $req->requester_id,
                'type'              => 'in',
                'source'            => 'pin_purchase',
                'amount'            => $amount,
                'notes'             => "Pembelian PIN #{$req->id} ({$req->qty} x " . number_format($req->unit_price,0,',','.') . ")",
                'payment_channel'   => $data['payment_method'],
                'payment_reference' => $data['payment_reference'],
            ]);
        }

        // === Generate PIN (idempotent) ===
        $remaining = max(0, $req->qty - (int)$req->generated_count);

        if ($remaining > 0) {
            for ($i=0; $i<$remaining; $i++) {
                $code = strtoupper(Str::random(16));
                ActivationPin::create([
                    'code'           => $code,
                    'status'         => 'unused',
                    'bagan'          => 1,
                    'price'          => $req->unit_price,
                    'purchased_by'   => $req->requester_id,
                    'pin_request_id' => $req->id,
                ]);
                $newCodes->push($code);
            }
            $req->generated_count += $remaining;
        }

        // Set final status generated
        $req->status = 'generated';
        $req->generated_at = now();
        $req->save();
    });

    // === Kirim WA ke member (setelah commit) ===
    $req->load('requester:id,name,no_telp');

    // Ambil semua kode (bukan hanya yang baru) agar pesan konsisten
    $allCodes = ActivationPin::where('pin_request_id',$req->id)
        ->orderBy('id')->pluck('code')->implode(', ');

    $msg = "Assalamu'alaikum {$req->requester->name},\n\n"
         . "PIN Aktivasi Anda sudah TERBIT ✅\n"
         . "Jumlah: {$req->generated_count}\n"
         . "Kode: {$allCodes}\n\n"
         . "Gunakan PIN untuk aktivasi downline.";

    $this->sendWhatsApp($req->requester->no_telp, $msg);

    return back()->with('success', 'Approved & PIN dibuat. WA terkirim ke member.');
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
    protected function sendWhatsApp($phone, $message)
    {
        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        }

        try {
            $client = new Client();
            $client->post('https://api.fonnte.com/send', [
                'headers' => [
                    'Authorization' => env('FONNTE_TOKEN'),
                ],
                'form_params' => [
                    'target' => $phone,
                    'message' => $message,
                    'delay' => 2,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("❌ Gagal kirim WA ke {$phone}: " . $e->getMessage());
        }
    }
}
