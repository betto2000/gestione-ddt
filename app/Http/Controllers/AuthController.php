<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Tenta il login normale con email/password
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $deviceId = $request->device_id;
            $certify = $request->certify_device === true;

            // Genera un token sicuro
            $token = Str::random(60);

            // Cerca se questo dispositivo è già associato all'utente
            $deviceToken = DeviceToken::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->first();

                if ($deviceToken) {

                    // Aggiorna direttamente con una query SQL raw
                    DB::update('UPDATE device_tokens SET token = ?, certified_at = GETDATE() WHERE id = ?', [
                        $token,
                        $deviceToken->id
                    ]);
                } else {

                    DB::insert('INSERT INTO device_tokens (user_id, device_id, token, certified_at, created_at, updated_at) VALUES (?, ?, ?, GETDATE(), GETDATE(), GETDATE())', [
                        $user->id,
                        $deviceId,
                        $token
                    ]);
                }

            return response()->json([
                'user' => $user,
                'token' => $token,
                'device_certified' => $certify || ($deviceToken && $deviceToken->certified_at)
            ]);
        }

        return response()->json(['message' => 'Credenziali non valide'], 401);
    }

    public function checkDevice(Request $request)
    {
        $deviceId = $request->header('Device-ID') ?? $request->cookie('device_id');
        $token = $request->header('Device-Token') ?? $request->cookie('device_token');

        if (!$deviceId || !$token) {
            return response()->json(['authenticated' => false]);
        }

        $deviceToken = DeviceToken::where('device_id', $deviceId)
            ->where('token', $token)
            ->whereNotNull('certified_at')
            ->first();

        if (!$deviceToken) {
            return response()->json(['authenticated' => false]);
        }

        $user = User::find($deviceToken->user_id);

        if (!$user) {
            return response()->json(['authenticated' => false]);
        }

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        if ($request->device_id) {
            // Opzionale: rimuovere solo il token attuale dal dispositivo senza decertificarlo
            DeviceToken::where('device_id', $request->device_id)
                ->where('user_id', Auth::id())
                ->update(['token' => null]);
        }

        return response()->json(['message' => 'Logout effettuato con successo']);
    }
}
