<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password) || !$user->forApp) {
        return response()->json([
            'status' => false,
            'message' => 'validation error',
            'errors' => 'The provided credentials are incorrect.'
        ], 403);
    }

    return $user->createToken($request->device_name)->plainTextToken;
});

Route::post('register', function (Request $request) {
    //Validated
    $validateUser = $request->validate(
        [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'device_name' => 'required'
        ]
    );

    // if ($validateUser->fails()) {
    //     return response()->json([
    //         'status' => false,
    //         'message' => 'validation error',
    //         'errors' => $validateUser->errors()
    //     ], 401);
    // }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'forApp' => true
    ]);

    return response()->json([
        'status' => true,
        'message' => 'User Created Successfully',
        'token' => $user->createToken($request->device_name)->plainTextToken
    ], 200);
});
