<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ASSNController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function notify(Request $request)
    {
        try {
            $signedResponses = (array)$request;
        } finally {
        }
        for ($i = 0; $i < count($signedResponses); $i++) {
            $signedResponse = $signedResponses[$i];
            $payload = ASSNController::decode_signed_response($signedResponse);
            Log::info($payload);
            echo $payload;
        }

        return $request;
    }

    function decode_signed_response($signed_response)
    {
        // Base64 decode the signedResponse
        $decoded_data = base64_decode($signed_response);

        // Decompress the payload using gzip
        $decompressed_data = gzdecode($decoded_data);

        // Parse the JSON payload
        $json_payload = json_decode($decompressed_data, true);

        return $json_payload;
    }

    function verify_app_store_signature($decoded_payload)
    {
        // Load Apple's public key (PEM format) from the extracted file
        $public_key_pem = file_get_contents("AppleWWDRCAG3_public.pem");

        // Extract the signature from the incoming request headers
        $signature = "<signature_from_headers_here>";

        // signedResponse and transaction_id from previous steps
        $signed_response = "<your_signed_response_here>";
        $transaction_id = $decoded_payload['unified_receipt']['pending_renewal_info'][0]['transaction_id'];

        // Verify the signature
        $is_verified = ASSNController::verify_app_store_signature_body($signed_response, $transaction_id, $signature, $public_key_pem);

        if ($is_verified) {
            echo "Signature is valid!\n";
        } else {
            echo "Invalid signature!\n";
        }
    }

    function verify_app_store_signature_body($signed_response, $transaction_id, $signature, $public_key_pem)
    {
        // Decode the signature
        $decoded_signature = base64_decode($signature);

        // Prepare the payload for verification
        $payload_to_verify = $signed_response . $transaction_id;

        // Verify the signature
        $verified = openssl_verify($payload_to_verify, $decoded_signature, $public_key_pem, OPENSSL_ALGO_SHA256);

        return $verified === 1;
    }
}
