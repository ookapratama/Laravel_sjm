<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use App\Events\NotificationReceived;
use App\Models\Notification;
use App\Models\User;
class PreRegistrationVerificationController extends Controller
{
    public function index()
    {
        $url = route('finance.pre-registrations');

    // Tandai semua notifikasi terkait URL ini sebagai read
    Notification::where('url', $url)
        ->where('is_read', false)
        ->update(['is_read' => true]);
        $preRegistrations = PreRegistration::where('status','pending')->get();
        return view('finance.pre-registrations', compact('preRegistrations'));
    }
    public function verify($id)
    {
        $pre = PreRegistration::findOrFail($id);

        if ($pre->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Status sudah diproses sebelumnya.'
            ], 422);
        }

        $pre->status = 'payment_verified';
        $pre->save();

           $adminuser = User::where('role', 'admin')->get();

        foreach ($adminuser as $admin) {
            // Simpan ke database (jika perlu histori)
            Notification::create([
                'user_id' => $admin->id,
                'message' => 'Member Baru Telah diverifikasi: ' . $pre->name,
                'url' => route('admin.pre-register.form'),
            ]);

            // Broadcast via Pusher
            event(new NotificationReceived($admin->id, [
                'type' => 'new_referral', // atau 'preregistration_received' jika Anda ingin beda
                'message' => 'Member Baru Telah diverifikasi: ' . $pre->name,
                'url' => route('admin.pre-register.form'),
                'created_at' => now()->toDateTimeString()
            ]));
        }


        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil diverifikasi.'
        ]);
    }


    public function reject($id)
    {
        $pre = PreRegistration::findOrFail($id);

        if ($pre->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Status sudah diproses sebelumnya.'
            ], 422);
        }

        $pre->status = 'rejected';
        $pre->save();

        return response()->json([
            'success' => true,
            'message' => 'Pre-registration telah ditolak.'
        ]);
    }
}