<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        //Validated
        try {
            $request->validate(
                [
                    'login' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required'
                ]
            );

            $user = User::create([
                'login' => $request->login,
                'login_type' => 'email',
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'for_app' => true
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'token' => $user->createToken($request->login)->plainTextToken
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 401);
        }
    }

    /**
     * Login an existing user.
     *
     * @param  Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password) || !$user->for_app) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => 'The provided credentials are incorrect.'
            ], 403);
        }

        return $user->createToken($user->login)->plainTextToken;
    }

    /**
     * Login an existing user with Apple.
     *
     * @param  Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sign_in_with_apple(Request $request)
    {
        $request->validate([
            'auth_code' => 'required'
        ]);

        // Access Apple Endpoint and Validate authorization code
        // Obtain login handle

        // 1. generate JWT token signed with secrets/AuthKey_6TB8Z9A3BT.pem
        $private_key = Env('APPLE_ID_PRIVATE_KEY');
        $keyId = Env('APPLE_ID_KEY_ID');
        $teamId = Env('APPLE_ID_TEAM_ID');

        $token_duration = 60 * 60;  # Token duration in seconds

        $header = [
            'alg' => 'ES256',
            'kid' => $keyId
        ];

        $payload = [
            'iss' => $teamId,
            'iat' => time(),
            'exp' => time() + $token_duration,
            'aud' => 'https://appleid.apple.com',
            'sub' => 'com.yfujiki.signinWithApple-flutter'
        ];

        $token = JWT::encode($payload, $private_key, 'ES256', $keyId, $header);

        // 2. send auth_code, JWT token, and client_id to Apple
        $url = 'https://appleid.apple.com/auth/token';
        $data = array(
            'client_id' => 'com.yfujiki.signinWithApple-flutter',
            'client_secret' => $token,
            'code' => $request->auth_code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => ''
        );
        $data = http_build_query($data);
        $header = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json'
        );
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $body = json_decode($response, true);

        // 3. decode id_token, obtain email and sub as login

        if (array_key_exists('id_token', $body) == false) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => 'The provided credentials are incorrect.'
            ], 403);
        }

        $id_token = $body['id_token'];
        list($header, $payload, $signature) = explode('.', $id_token);
        $header = JWT::jsonDecode(JWT::urlsafeB64Decode($header));
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payload));;

        $login = $payload->sub;
        $user = User::where('login', $login)->where('login_type', 'apple')->first();

        if (!$user) {
            $user = User::create([
                'login' => $login,
                'login_type' => 'apple',
                'email' => $payload->email,
                'for_app' => true
            ]);
        }

        return $user->createToken($login)->plainTextToken;
    }
}
