<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $deviceId = $request->device_id;
            $certifyDevice = $request->certify_device === true;

            // Genera un token sicuro
            $token = Str::random(60);

            // Verifica se il dispositivo esiste già
            $query = "SELECT id FROM device_tokens WHERE user_id = ? AND device_id = ?";
            $deviceExists = DB::select($query, [$user->id, $deviceId]);

            if (count($deviceExists) > 0) {
                // Aggiorna dispositivo esistente
                $deviceId = $deviceExists[0]->id;

                $updateQuery = "UPDATE device_tokens SET
                    token = ?,
                    certified_at = GETDATE(),
                    ip_address = ?,
                    updated_at = GETDATE()
                    WHERE id = ?";

                DB::update($updateQuery, [
                    $token,
                    $request->ip(),
                    $deviceId
                ]);
            } else {
                // Inserisci nuovo dispositivo
                $insertQuery = "INSERT INTO device_tokens
                    (user_id, device_id, token, certified_at, ip_address, created_at, updated_at)
                    VALUES (?, ?, ?, GETDATE(), ?, GETDATE(), GETDATE())";

                DB::insert($insertQuery, [
                    $user->id,
                    $deviceId,
                    $token,
                    $request->ip()
                ]);
            }

            return response()->json([
                'user' => $user,
                'token' => $token,
                'device_certified' => $certifyDevice
            ]);
        }

        return response()->json(['message' => 'Credenziali non valide'], 401);
    }

    public function checkDevice(Request $request)
    {
        $deviceId = $request->header('Device-ID');
        $token = $request->header('Device-Token');

        if (!$deviceId || !$token) {
            return response()->json(['authenticated' => false]);
        }

        // Verifica il dispositivo con query diretta
        $query = "SELECT u.id, u.name, u.email
                  FROM device_tokens d
                  JOIN users u ON d.user_id = u.id
                  WHERE d.device_id = ?
                  AND d.token = ?
                  AND d.certified_at IS NOT NULL";

        $result = DB::select($query, [$deviceId, $token]);

        if (count($result) === 0) {
            return response()->json(['authenticated' => false]);
        }

        $user = $result[0];

        return response()->json([
            'authenticated' => true,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        // Elimina il token del dispositivo se fornito
        $deviceId = $request->header('Device-ID');
        if ($deviceId) {
            DB::delete('DELETE FROM device_tokens WHERE device_id = ?', [$deviceId]);
        }

        return response()->json(['message' => 'Logout effettuato con successo']);
    }
}
