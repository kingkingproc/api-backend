<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request) {
        $fields = $request->validate([
            'name_first' => 'required|string',
            'name_last' => 'required|string',
            'sub' => 'required|string'
        ]);

        $user = Patient::create([
            'name_first' => $fields['name_first'],
            'name_last' => $fields['name_last'],
            'sub' => $fields['sub']
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'patient' => $user,
            'token' => $token
        ];

        return response($response, 201);

    }


    public function login(Request $request) {
        $fields = $request->validate([
            'sub' => 'required|string'
        ]);

        $user = Patient::where('sub', $fields['sub'])->first();

        if(!$user) {
            return response(['message' => 'bad cred']);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'patient' => $user,
            'token' => $token
        ];

        return response($response, 201);

    }


    public function logout(Request $request) {
        auth()->user()->tokens()->delete();

        return ['message' => 'logged out'];
    }    
}
