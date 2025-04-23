<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckDeviceToken
{
    public function handle(Request $request, Closure $next)
    {
        // Se l'utente è già autenticato tramite Sanctum, procedi
        if (Auth::check()) {
            return $next($request);
        }

        // Altrimenti verifica se c'è un dispositivo certificato
        $deviceId = $request->header('Device-ID');
        $token = $request->header('Device-Token');

        if (!$deviceId || !$token) {
            return response()->json(['message' => 'Autenticazione richiesta'], 401);
        }

        // Trova l'utente associato al dispositivo
        $query = "SELECT user_id FROM device_tokens
                  WHERE device_id = ? AND token = ? AND certified_at IS NOT NULL";

        $result = DB::select($query, [$deviceId, $token]);

        if (count($result) === 0) {
            return response()->json(['message' => 'Dispositivo non autorizzato'], 401);
        }

        // Login manuale dell'utente
        $userId = $result[0]->user_id;
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'Utente non trovato'], 401);
        }

        Auth::login($user);

        return $next($request);
    }
}
