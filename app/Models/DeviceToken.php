<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'token'
    ];

    // Disabilita i timestamp automatici di Laravel
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Metodo personalizzato per inserire i record
    public static function createToken($userId, $deviceId, $token, $certifiedAt = null)
    {
        // Inserimento manuale con formattazione corretta della data per SQL Server
        $sql = "INSERT INTO device_tokens (user_id, device_id, token, certified_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, GETDATE(), GETDATE())";

        return DB::insert($sql, [
            $userId,
            $deviceId,
            $token,
            $certifiedAt
        ]);
    }

    // Metodo personalizzato per aggiornare i record
    public static function updateToken($userId, $deviceId, $token)
    {
        $sql = "UPDATE device_tokens
                SET token = ?, updated_at = GETDATE()
                WHERE user_id = ? AND device_id = ?";

        return DB::update($sql, [$token, $userId, $deviceId]);
    }

    // Metodo personalizzato per certificare un dispositivo
    public static function certifyDevice($userId, $deviceId)
    {
        $sql = "UPDATE device_tokens
                SET certified_at = GETDATE(), updated_at = GETDATE()
                WHERE user_id = ? AND device_id = ?";

        return DB::update($sql, [$userId, $deviceId]);
    }
}
