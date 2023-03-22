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

        foreach ($signedResponse as $key => $value) {
            if ($key == "signedPayload") {
                // $payload = ASSNController::decode_signed_response($value);
                list($header, $payload, $signature) = explode('.', $value);
                $header = JWT::jsonDecode(JWT::urlsafeB64Decode($header));
                $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));;
                // Log::info(var_export($header, true));
                $root_cert = Storage::get('AppleWWDRCAG3_public.pem');
                // Log::info($root_cert);

                foreach ($payload as $pkey => $pvalue) {
                    if ($pkey == 'notificationType') {
                        Log::info("notificationType: " . $pvalue);
                    }
                    if ($pkey == 'subtype') {
                        Log::info("subtype: " . $pvalue);
                    }
                    if ($pkey == 'data') {
                        foreach ($pvalue as $dkey => $dvalue) {
                            if ($dkey == 'signedTransactionInfo') {
                                $signedTransactionInfo = $dvalue;
                                list($theader, $tpayload, $tsignature) = explode('.', $signedTransactionInfo);

                                $theader = JWT::jsonDecode(JWT::urlsafeB64Decode($theader));
                                $tpayload = JWT::jsonDecode(JWT::urlsafeB64Decode($tpayload));;

                                if ($header == $theader) {
                                    Log::info("Transaction header is the same");
                                }

                                // Log::info(var_export($theader, true));
                                Log::info(var_export($tpayload, true));
                            }
                            if ($dkey == 'signedRenewalInfo') {
                                $signedRenewalInfo = $dvalue;
                                list($rheader, $rpayload, $rsignature) = explode('.', $signedRenewalInfo);

                                $rheader = JWT::jsonDecode(JWT::urlsafeB64Decode($rheader));
                                $rpayload = JWT::jsonDecode(JWT::urlsafeB64Decode($rpayload));;

                                if ($header == $rheader) {
                                    Log::info("Renewal header is the same");
                                }

                                // Log::info(var_export($rheader, true));
                                Log::info(var_export($rpayload, true));
                            }
                        }
                    }
                }

                // $last_cert_idx = count($header->x5c) - 1;
                // $last_cert = $header->x5c[$last_cert_idx];
                // Log::info($last_cert);
            }
        }
        return "hello";
    }
}
