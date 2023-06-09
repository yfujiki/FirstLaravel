<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppleSubscriptionController extends Controller
{
    // verify the receipt
    //   user_id: the user id
    //   product_id: the product id
    //   receipt: the receipt data
    public function verifyReceipt(Request $request)
    {
        // Log::info('Request: ' . $request);
        $receipt = $request->receipt;
        // Log::info('Receipt: ' . $receipt);
        Log::info('Product_id: ' . $request->product_id);
        Log::info('User_id: ' . $request->user_id);
        $password = Env('APPLE_APP_SHARED_SECRET');
        Log::info('Password: ' . $password);
        $url = 'https://sandbox.itunes.apple.com/verifyReceipt';
        // $url = 'https://buy.itunes.apple.com/verifyReceipt';
        $data = array(
            'receipt-data' => $receipt,
            'exclude-old-transactions' => true,
            'password' => $password
        );
        $data = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        curl_close($ch);

        $body = json_decode($response);

        // 1. Check the status code
        $status = $body->status;
        Log::info('Status code is ' . $status);

        if ($status != 0) {
            return response()->json([
                'status' => false,
                'message' => 'Status code is ' . $status
            ], 401);
        }

        $latest_receipt_info = last($body->latest_receipt_info);
        Log::info('Original transaction id: ' . $latest_receipt_info->original_transaction_id);

        // 2. Check the product id
        $product_id = $latest_receipt_info->product_id;
        Log::info($product_id . ' != ' . $request->product_id . ' ?');

        if ($product_id != $request->product_id) {
            return response()->json([
                'status' => false,
                'message' => 'Product id mismatch'
            ], 401);
        }

        // 3. Check the expiration date
        $expires_date = $latest_receipt_info->expires_date_ms;
        $now = round(microtime(true) * 1000);
        $ts = Carbon::createFromTimestampMs($expires_date)->toDateTimeString();
        Log::info('Receipt expires at ' . $ts);
        Log::info($expires_date . ' < ' . $now . ' ?');

        if ($expires_date < $now) {
            return response()->json([
                'status' => false,
                'message' => 'Receipt expired'
            ], 401);
        }

        // 4. Add the original transaction id to the database
        Log::info("Will add the original transaction id/current product id to the database");

        return response()->json([
            'current_product_id' => $product_id,
            'message' => 'Receipt verification success'
        ], 200);
    }
}
