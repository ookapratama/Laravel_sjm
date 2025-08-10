<?php
namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.sanitize');
        Config::$is3ds = config('midtrans.3ds');
    }

    public function createQrisTransaction($order_id, $gross_amount, $customer_name)
    {
        $params = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => $gross_amount,
            ],
            'customer_details' => [
                'first_name' => $customer_name,
            ]
        ];

        return \Midtrans\CoreApi::charge($params);
    }
}
