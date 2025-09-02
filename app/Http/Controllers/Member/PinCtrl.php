<?php

namespace App\Http\Controllers\Member;

use App\Events\MemberPinRequest;
use App\Events\UplineTransferPin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivationPin;   // <-- tambahkan
use App\Models\Notification;
use App\Models\PinRequest;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

// <-- tambahkan

class PinCtrl extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->is_active, 403);

        $requests = PinRequest::where('requester_id', auth()->id())
            ->latest()
            ->get();
$userId = auth()->id();
        $pins = DB::table('activation_pins as ap')
            ->leftJoin('users as purchaser', 'ap.purchased_by', '=', 'purchaser.id')
            ->leftJoin('users as transferred', 'ap.transferred_to', '=', 'transferred.id')
            ->leftJoin('users as used', 'ap.used_by', '=', 'used.id')
             ->where(function ($q) use ($userId) {
                    $q->where('ap.purchased_by',  $userId)
                    ->orWhere('ap.transferred_to', $userId)
                    ->orWhere('ap.used_by', $userId); // kalau mau tampil juga PIN yang dipakai user
                })
            ->orwhere('ap.transferred_to',$userId)
            ->orderBy('ap.status', 'asc')
            ->select(
                'ap.*',
                'purchaser.name as purchaser_name',
                'purchaser.username as purchaser_username',
                'used.name as used_name',
                'used.username as used_username',
                'transferred.name as transferred_name',
                'transferred.username as transferred_username',
            )
            ->get();

        $hasOpen = $requests->contains(fn($r) => in_array($r->status, ['requested', 'finance_approved']));

     

        $downlines = collect(DB::select("
                WITH RECURSIVE downlines AS (
                    SELECT id, username,name, upline_id, sponsor_id,email,position
                    FROM users
                    WHERE upline_id = ?
    
                    UNION ALL
    
                    SELECT u.id, u.username,u.name, u.upline_id, u.sponsor_id,u.email,u.position
                    FROM users u
                    INNER JOIN downlines d ON u.upline_id = d.id
                )
                SELECT * FROM downlines
            ", [$userId]));
        return view('member.pin', [
            'requests' => $requests,
            'pins'     => $pins,
            'hasOpen'  => $hasOpen,
            'downlines'  => $downlines,
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



        $req = PinRequest::create([
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
        $this->notifyFinanceOnPinOrder($req);
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


        return back()->with('success', 'Request dikirim. Menunggu verifikasi Finance.');
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
        $urlApprove = route('finance.pin.index');

        $msg =
            "$judul\n\n"
            . "ID: #$id\n"
            . "Pemesan: $requester ($hpReq)\n"
            . "Jumlah: $qty PIN\n"
            . "Harga/Unit: $unit\n"
            . "Total: $total\n"
            . "Metode: $method\n"
            . "Status: REQUESTED\n\n"
            . "Periksa & proses di: $urlApprove";

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
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
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

    public function transfer(Request $request)
    {
        $request->validate([
            'pin_id'         => 'required|exists:activation_pins,id',
            'downline_id'    => 'required|exists:users,id',
            'transfer_notes' => 'nullable|string|max:500',
        ]);
        
        $user = auth()->user();

        try {
            DB::beginTransaction();
            
            // Ambil PIN milik user login yang masih bisa ditransfer + kunci row
            $pin = ActivationPin::query()
            ->whereKey($request->pin_id)
            ->where('purchased_by', $user->id)
            ->where('status', 'unused') // sesuaikan jika istilah status beda
            ->whereNull('transferred_to') 
            ->lockForUpdate()
            ->first();
            
            if (!$pin) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'PIN tidak ditemukan atau tidak dapat ditransfer'
                ], 404);
            }


            $downlineRow = DB::selectOne("
            WITH RECURSIVE downlines AS (
                SELECT id, username, name, upline_id, sponsor_id, email, position
                FROM users
                WHERE upline_id = ?

                UNION ALL

                SELECT u.id, u.username, u.name, u.upline_id, u.sponsor_id, u.email, u.position
                FROM users u
                INNER JOIN downlines d ON u.upline_id = d.id
            )
            SELECT * FROM downlines WHERE id = ? LIMIT 1
        ", [$user->id, $request->downline_id]);

            if (!$downlineRow) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'User bukan downline Anda'
                ], 403);
            }


            $downline = User::findOrFail($request->downline_id);

            // Update PIN → tandai sudah ditransfer
            $pin->update([
                'transferred_to'   => $downline->id,
                'status'           => 'transferred',
                'transferred_date' => now(),
                'transferred_notes' => $request->transfer_notes
            ]);

            // Simpan notifikasi ke database
            Notification::create([
                'user_id' => $downline->id,
                'message' => 'Upline anda telah mentransfer pin terbaru ke anda, Silahkan cek/refresh halaman Dashboard anda',
                'url'     => route('member'),
            ]);


            event(new \App\Events\UplineTransferPin($downline->id, [
                'type'       => 'finance_approved',
                'message'    => 'Upline anda telah mentransfer pin terbaru ke anda, Silahkan cek/refresh halaman Dashboard anda',
                'url'        => route('member'),
                'created_at' => now()->toDateTimeString(),
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "PIN {$pin->code} berhasil ditransfer ke {$downlineRow->name}",
                'data' => [
                    'pin_code'      => $pin->code,
                    'recipient'     => $downlineRow->name,
                    'transfer_date' => $pin->transferred_date,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PIN Transfer Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mentransfer PIN'
            ], 500);
        }
    }

    private function getUserDownlines($user)
    {
        $downlines = collect();

        // Get left and right children
        $leftChild = $user->getLeftChild();
        $rightChild = $user->getRightChild();

        if ($leftChild) {
            $downlines->push((object)[
                'id' => $leftChild->id,
                'name' => $leftChild->name,
                'username' => $leftChild->username,
                'position' => 'left'
            ]);

            // Get children of left child
            $leftGrandChildren = $this->getDirectChildren($leftChild);
            $downlines = $downlines->merge($leftGrandChildren);
        }

        if ($rightChild) {
            $downlines->push((object)[
                'id' => $rightChild->id,
                'name' => $rightChild->name,
                'username' => $rightChild->username,
                'position' => 'right'
            ]);

            // Get children of right child
            $rightGrandChildren = $this->getDirectChildren($rightChild);
            $downlines = $downlines->merge($rightGrandChildren);
        }

        return $downlines;
    }

    private function getDirectChildren($user)
    {
        $children = collect();

        $leftChild = $user->getLeftChild();
        $rightChild = $user->getRightChild();

        if ($leftChild) {
            $children->push((object)[
                'id' => $leftChild->id,
                'name' => $leftChild->name,
                'username' => $leftChild->username,
                'position' => 'left'
            ]);
        }

        if ($rightChild) {
            $children->push((object)[
                'id' => $rightChild->id,
                'name' => $rightChild->name,
                'username' => $rightChild->username,
                'position' => 'right'
            ]);
        }

        return $children;
    }

    private function isUserDownline($upline, $downline)
    {
        if (!$downline) return false;

        // Check if user is direct child
        $leftChild = $upline->getLeftChild();
        $rightChild = $upline->getRightChild();

        if ($leftChild && $leftChild->id === $downline->id) return true;
        if ($rightChild && $rightChild->id === $downline->id) return true;

        // Check grand children (optional, adjust based on your business logic)
        if ($leftChild) {
            $leftGrandLeft = $leftChild->getLeftChild();
            $leftGrandRight = $leftChild->getRightChild();
            if ($leftGrandLeft && $leftGrandLeft->id === $downline->id) return true;
            if ($leftGrandRight && $leftGrandRight->id === $downline->id) return true;
        }

        if ($rightChild) {
            $rightGrandLeft = $rightChild->getLeftChild();
            $rightGrandRight = $rightChild->getRightChild();
            if ($rightGrandLeft && $rightGrandLeft->id === $downline->id) return true;
            if ($rightGrandRight && $rightGrandRight->id === $downline->id) return true;
        }

        return false;
    }
}
