<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    // Disabilita i timestamps automatici per evitare problemi con SQL Server
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'device_id', 'token', 'ip_address', 'user_agent', 'certified_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
