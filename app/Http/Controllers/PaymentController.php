<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\MidtransService;

public function payWithQris(Request $request, MidtransService $midtrans)
{
    $orderId = 'ORDER-' . time();
    $response = $midtrans->createQrisTransaction($orderId, $request->amount, $request->name);

    if (isset($response->actions)) {
        $qrisImage = collect($response->actions)->where('name', 'generate-qr-code')->first()['url'];
        return view('payment.qris', compact('qrisImage', 'orderId'));
    }

    return back()->with('error', 'Gagal membuat transaksi QRIS.');
}
