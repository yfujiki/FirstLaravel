<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Storage;

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
        $signedResponse = json_decode($request->getContent());

        $signedPaylod = $signedResponse->signedPayload;
        list($header, $payload, $signature) = explode('.', $signedPaylod);
        $header = JWT::jsonDecode(JWT::urlsafeB64Decode($header));
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));;
        $root_cert = Storage::get('AppleWWDRCAG3_public.pem');

        $notificationType = $payload->notificationType;

        if (property_exists($payload, "subtype")) {
            $subtype = $payload->subtype;
        } else {
            $subtype = null;
        }
        // Log::info("payload" . var_export($payload, true));
        Log::info("notificationType: " . $notificationType);
        Log::info("subtype: " . $subtype);

        $data = $payload->data;

        // transaction
        $signedTransactionInfo = $data->signedTransactionInfo;

        list($theader, $tpayload, $tsignature) = explode('.', $signedTransactionInfo);

        $theader = JWT::jsonDecode(JWT::urlsafeB64Decode($theader));
        $tpayload = JWT::jsonDecode(JWT::urlsafeB64Decode($tpayload));;

        if ($header == $theader) {
            Log::info("Transaction header is the same");
        }

        Log::info("Origianl transaction id: " . $tpayload->originalTransactionId);

        if ($notificationType == 'SUBSCRIBED') {
            Log::info("SUBSCRIBED to " . $tpayload->productId);
        }
        if ($notificationType == 'DID_CHANGE_RENEWAL_PREF' && $subtype == 'UPGRADE') {
            Log::info("UPGRADE to " . $tpayload->productId);
        }
        // if ($notificationType == 'DID_CHANGE_RENEWAL_PREF' && $subtype == 'DOWNGRADE') {
        //     Log::info("DOWNGRADE from " . $tpayload->productId);
        // }
        if ($notificationType == 'DID_RENEW') { // To monitor downgrading
            Log::info("DID_RENEW for " . $tpayload->productId);
        }
        if ($notificationType == 'EXPIRED') {
            Log::info($tpayload->productId . " EXPIRED");
        }
        if ($notificationType == 'GRACE_PERIOD_EXPIRED') {
            Log::info($tpayload->productId . " GRACE_PERIOD_EXPIRED");
        }

        // renewal
        $signedRenewalInfo = $data->signedRenewalInfo;
        list($rheader, $rpayload, $rsignature) = explode('.', $signedRenewalInfo);

        $rheader = JWT::jsonDecode(JWT::urlsafeB64Decode($rheader));
        $rpayload = JWT::jsonDecode(JWT::urlsafeB64Decode($rpayload));;

        if ($header == $rheader) {
            Log::info("Renewal header is the same");
        }

        // Log::info(var_export($rheader, true));
        // Log::info(var_export($rpayload, true));

        Log::info("Auto renew status for " . $rpayload->productId . ": " . $rpayload->autoRenewStatus);

        return response()->json([
            'status' => true,
            'message' => 'OK'
        ], 200);
    }
}
