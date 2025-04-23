<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DeviceToken;
use App\Models\User;

class VerifyCertifiedDevice
{
    public function handle(Request $request, Closure $next)
    {
        $deviceId = $request->header('Device-ID');
        $token = $request->header('Device-Token');

        if (!$deviceId || !$token) {
            // Prova a leggere i valori dai cookies
            $deviceId = $request->cookie('device_id');
            $token = $request->cookie('device_token');

            if (!$deviceId || !$token) {
                return response()->json(['message' => 'Dispositivo non identificato'], 401);
            }
        }

        // Cerca il dispositivo certificato
        $deviceToken = DeviceToken::where('device_id', $deviceId)
            ->where('token', $token)
            ->whereNotNull('certified_at')
            ->first();

        if (!$deviceToken) {
            return response()->json(['message' => 'Dispositivo non certificato'], 401);
        }

        // Carica l'utente associato al dispositivo
        $user = User::find($deviceToken->user_id);

        if (!$user) {
            return response()->json(['message' => 'Utente non trovato'], 401);
        }

        // Imposta l'utente nella richiesta
        Auth::login($user);

        return $next($request);
    }
}
