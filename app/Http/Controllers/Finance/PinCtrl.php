<?php

namespace App\Http\Controllers\Finance;

use App\Events\NotificationReceived;
use App\Events\PinRequestAprrovedByFinance;
use App\Events\PinRequestRejected;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PinRequest;
use App\Models\CashTransaction;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PinCtrl extends Controller
{
    public function index()
    {
        $url = route('member.pin.index');

        Notification::where('url', $url)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('finance.pin_request', [
            'list' => PinRequest::with('requester')->latest()->get()
        ]);
    }


    public function approve(Request $r, $id)
    {
        try {
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

            $financeUsers = User::where('role', 'admin')->get();

            foreach ($financeUsers as $finance) {
                // Simpan ke database (jika perlu histori)
                Notification::create([
                    'user_id' => $finance->id,
                    'message' => 'Finance menyetujui aktivasi pin : ' . auth()->user()->name . ' dengan ID : ' . auth()->id(),
                    'url' => route('admin.admin.pin.index'),
                ]);

                // Broadcast via Pusher
                event(new PinRequestAprrovedByFinance($finance->id, [
                    'type' => 'finance_approved', // atau 'preregistration_received' jika Anda ingin beda
                    'message' => 'Finance menyetujui aktivasi pin : ' . auth()->user()->name . ' dengan ID : ' . auth()->id(),
                    'url' => route('admin.admin.pin.index'),
                    'created_at' => now()->toDateTimeString()
                ]));
            }

            \Log::info('PIN Request Approved and Notification Sent to Admin', [
                'pin_request_id' => $r->id,
                'user_id' => $r->requester_id,
            ]);


            return back()->with('success', 'Approved. Menunggu Admin generate.');
        } catch (\Exception $e) {
            \Log::error('Error rejecting PIN request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $r, $id)
    {
        try {
            $notes = $r->validate(['finance_notes' => 'required|string|max:500'])['finance_notes'];
            $req = PinRequest::findOrFail($id);
            if ($req->status !== 'requested') return back()->with('error', 'Invalid.');

            $req->update([
                'status'     => 'finance_rejected',
                'finance_notes' => $notes,
                'finance_id' => auth()->id(),
                'finance_at' => now()
            ]);
            $memberUser = User::find($req->requester_id);
            // dd($req->requester_id);

            // Simpan ke database (jika perlu histori)
            Notification::create([
                'user_id' => $req->requester_id,
                'message' => 'Pihak Finance menolak Permintaan  pin anda.',
                'url' => route('member.pin.index'),
            ]);

            // Broadcast via Pusher
            event(new PinRequestRejected($req->requester_id, [
                'type' => 'rejected_pin', // atau 'preregistration_received' jika Anda ingin beda
                'message' => 'Pihak Finance menolak Permintaan  pin anda.',
                'url' => route('member.pin.index'),
                'created_at' => now()->toDateTimeString()
            ]));

            \Log::info('PIN Request Rejected and Notification Sent', [
                'pin_request_id' => $req->id,
                'user_id' => $req->requester_id,
            ]);

            return back()->with('success', 'Ditolak.');
        } catch (\Exception $e) {
            \Log::error('Error rejecting PIN request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
