<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtransHandler(Request $request)
    {
        $data = $request->all();

        $signatureKey = $data['signature_key'];
        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        $serverKey = env('MIDTRANS_SERVER_KEY');

        $Mysignature = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);


        $transactionStatus = $data['transaction_status'];
        $type = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        if ($Mysignature !== $signatureKey) {
            return response()->json(['status' => 'error', 'message' => 'Invalid Signature'], 400);
        }

        $realOrderId = explode('-', $orderId)[1];
        $order = Order::where('id', $realOrderId)->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 400);
        }

        if ($order->status === 'success') {
            return response()->json(['status' => 'error', 'message' => 'Action is not permitted'], 405);
        }

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $order->status = 'challenge';
            } else if ($fraudStatus == 'accept') {
                $order->status = 'success';
            }
        } else if ($transactionStatus == 'settlement') {
            $order->status = 'success';
        } else if (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            $order->status = 'failure';
        } else if ($transactionStatus == 'pending') {
            $order->status = 'pending';
        }

        $log = [
            'status' => $transactionStatus,
            'raw_response' => json_encode($data),
            'order_id' => $realOrderId,
            'payment_type' => $type,
        ];

        $savedLog = PaymentLog::create($log);
        $order->save();

        if ($order->status === 'success') {
            // give access to premium course
            $response = createPremiunAccess([
                'user_id' => $order->user_id,
                'course_id' => $order->course_id,
            ]);

            return response()->json($response);
        }

        return response()->json('ok');
    }
}
