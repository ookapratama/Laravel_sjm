<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MidtransController extends Controller
{
   public function callback(Request $request)
{
    $payload = $request->all();

    // Validasi signature (opsional tapi disarankan)
    $signatureKey = $payload['signature_key'];
    $orderId = $payload['order_id'];
    $statusCode = $payload['status_code'];
    $grossAmount = $payload['gross_amount'];

    $serverKey = config('midtrans.server_key');
    $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

    if ($signatureKey !== $expectedSignature) {
        \Log::warning('Midtrans callback - invalid signature');
        return response()->json(['message' => 'Invalid signature'], 403);
    }

    // Simpan atau update status pembayaran
    $transactionStatus = $payload['transaction_status'];

    if ($transactionStatus === 'settlement') {
        // tandai pesanan berhasil dibayar
    }

    return response()->json(['message' => 'OK']);
}


}
