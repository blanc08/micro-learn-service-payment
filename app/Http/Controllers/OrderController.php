<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $userId = $request->input('user_id');

        $orders = Order::query();

        if ($userId) {
            $orders->where('user_id', $userId);
        }

        $orders = $orders->get();

        return response()->json(['status' => 'success', 'data' => $orders]);
    }

    public function store(Request $request)
    {
        $user = $request->input('user');
        $course = $request->input('course');

        $order = Order::create([
            'user_id' => $user['id'],
            'course_id' => $course['id'],
        ]);

        $transactionDetail = [
            'order_id' => Str::random(5) . '-' . $order->id,
            'gross_amount' => $course['price']
        ];

        $itemDetail = [
            [
                'id' => $course['id'],
                'price' => $course['price'],
                'quantity' => 1,
                'name' => $course['name'],
                'brand' => "Course Online",
                'category' => "Course",
            ]
        ];

        $costumerDetail = [
            'first_name' => $user['name'],
            'email' => $user['email'],
        ];

        $midtransParams = [
            'transaction_details' => $transactionDetail,
            'item_details' => $itemDetail,
            'customer_details' => $costumerDetail,
        ];

        $midtransSnapUrl = $this->getMidtransSnapUrl($midtransParams);

        $order->snap_url = $midtransSnapUrl;
        $order->metadata = [
            'course_id' => $course['id'],
            'course_price' => $course['price'],
            'course_name' => $course['name'],
            'course_thumbnail' => $course['thumbnail'],
            'course_level' => $course['level'],
        ];

        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order created',
            'data' => $order,
        ]);
    }

    private function getMidtransSnapUrl($params)
    {
        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION', false);
        // Set sanitization on (default)
        \Midtrans\Config::$isSanitized = (bool) env('MIDTRANS_IS_SANITIZED', true);
        // Set 3DS transaction for credit card to true
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_IS_3DS', true);

        $snapUrl = \Midtrans\Snap::getSnapUrl($params);

        return $snapUrl;
    }
}
