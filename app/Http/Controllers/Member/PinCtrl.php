<?php

namespace App\Http\Controllers\Member;

use App\Events\MemberPinRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivationPin;   // <-- tambahkan
use App\Models\Notification;
use App\Models\PinRequest;
use App\Models\User;
use GuzzleHttp\Client;
     // <-- tambahkan

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

    public function store(Request $r)
    {
        abort_unless(auth()->user()->is_active, 403);
        $qty = (int) $r->validate(['qty' => 'required|integer|min:1|max:100'])['qty'];
        $unit = 750000;

        if (PinRequest::where('requester_id', auth()->id())->open()->exists()) {
            return back()->with('error', 'Masih ada request berjalan.');
        }
        $path = null;
        if ($r->hasFile('payment_proof')) {
            $path = $r->file('payment_proof')->store('payment-proofs/pin', 'public');
        }


// <<<<<<< ooka-dev
        PinRequest::create([
            'requester_id' => auth()->id(),
            'qty' => $qty,
            'unit_price' => $unit,
            'total_price' => $unit * $qty,
            'status' => 'requested',
            'payment_method' => $r->payment_method,
            'payment_reference' => $r->payment_reference ?? null,
            'payment_proof' => $path,
        ]);

        $financeUsers = User::where('role', 'finance')->get();

        foreach ($financeUsers as $finance) {
            // Simpan ke database (jika perlu histori)
            Notification::create([
                'user_id' => $finance->id,
                'message' => 'Member meminta aktivasi pin : ' . auth()->user()->name,
                'url' => route('finance.pin.index'),
            ]);

            // Broadcast via Pusher
            event(new MemberPinRequest($finance->id, [
                'type' => 'new_referral', // atau 'preregistration_received' jika Anda ingin beda
                'message' => 'Member meminta aktivasi pin : ' . auth()->user()->name,
                'url' => route('finance.pin.index'),
                'created_at' => now()->toDateTimeString()
            ]));
        }

        //return back()->with('success', 'Request dikirim. Menunggu verifikasi Finance.');
//         $req = PinRequest::create([
//             'requester_id'=>auth()->id(),
//             'qty'=>$qty, 
//             'unit_price'=>$unit, 
//             'total_price'=>$unit*$qty, 
//             'status'=>'requested',
//             'payment_method'=>$r->payment_method,
//             'payment_reference'=>$r->payment_reference ?? null,
//             'payment_proof'=> $path,
//         ]);
        $this->notifyFinanceOnPinOrder($req);
        
        return back()->with('success','Request dikirim. Menunggu verifikasi Finance.');
//>>>>>>> main
    }


private function notifyFinanceOnPinOrder(PinRequest $req): void
{
    $req->load('requester:id,name,no_telp,email');

    $judul   = "📦 ORDER PIN AKTIVASI BARU";
    $requester = $req->requester?->name ?? '-';
    $hpReq   = $req->requester?->no_telp ?? '-';
    $qty     = number_format($req->qty);
    $unit    = $this->rupiah($req->unit_price);
    $total   = $this->rupiah($req->total_price);
    $method  = strtoupper($req->payment_method ?? '-');
    $id      = $req->id;

    // (Opsional) link langsung ke halaman proses/approve
    $urlApprove = route('finance.pin.index'); // ganti dgn route detail/approve yang kamu punya

    $msg = 
        "$judul\n\n"
        ."ID: #$id\n"
        ."Pemesan: $requester ($hpReq)\n"
        ."Jumlah: $qty PIN\n"
        ."Harga/Unit: $unit\n"
        ."Total: $total\n"
        ."Metode: $method\n"
        ."Status: REQUESTED\n\n"
        ."Periksa & proses di: $urlApprove";

    // Ambil semua nomor Finance berdasarkan role
    $financePhones = User::where('role', 'finance')
    ->pluck('no_telp')   // ambil hanya kolom no_telp
    ->filter()           // buang null / kosong
    ->unique()           // hilangkan duplikat
    ->values();          // reset index jadi rapi


    // Fallback ENV kalau belum ada role/phone
     $phone = $financePhones->first();   // string "0852..."
if ($phone) $this->sendWhatsApp($phone, $msg);
}

/** Format rupiah sederhana */
private function rupiah($angka): string
{
    return 'Rp '.number_format((float)$angka,0,',','.');
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
