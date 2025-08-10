<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\PreRegistration;

class MidtransCallbackController extends Controller
{
      public function handle(Request $request)
    {
        Log::info('✅ Callback diterima: ' . json_encode($request->all()));

        $orderId = $request->order_id;
        $status = $request->transaction_status;
        $amount = $request->gross_amount;

        $prereg = PreRegistration::where('payment_proof', $orderId)->first();

        if (! $prereg) {
            Log::warning("⚠️ Order ID $orderId tidak ditemukan.");
            return response()->json(['message' => 'Order ID not found'], 404);
        }

        // ⚠️ Nonaktifkan signature check (for development only)
        Log::info("⚠️ Signature check dilewati untuk Order ID: $orderId");

        if ($status === 'settlement') {
            $prereg->status = 'paid';
            $prereg->save();

            Log::info("✅ Pre-registrasi {$prereg->email} telah dibayar.");
        }

        return response()->json(['message' => 'Callback processed']);
    }

}
