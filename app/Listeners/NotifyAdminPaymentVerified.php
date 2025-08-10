<?php
namespace App\Listeners;

use App\Events\PaymentVerifiedByFinance;
use App\Models\User;
use App\Models\Notification;

class NotifyAdminPaymentVerified
{
    public function handle(PaymentVerifiedByFinance $event)
    {
        $admins = User::where('role', 'admin')->get(); // Sesuaikan field 'role'

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'message' => "Pembayaran {$event->pre->name} telah diverifikasi Finance.",
                'url' => route('admin.pre-approvals'), // ganti sesuai rute admin approve
            ]);
        }
    }
}
