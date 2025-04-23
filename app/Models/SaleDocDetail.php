<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDocDetail extends Model
{
    protected $table = 'MA_SaleDocDetail';
    protected $primaryKey = ['SaleDocId', 'Line'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'SaleDocId', 'Line', 'Item', 'Description', 'UoM', 'Qty'
    ];

    public function saleDoc()
    {
        return $this->belongsTo(SaleDoc::class, 'SaleDocId', 'SaleDocId');
    }

    // Necessario per chiavi primarie composte in Laravel
    protected function setKeysForSaveQuery($query)
    {
        return $query->where('SaleDocId', $this->SaleDocId)
                     ->where('Line', $this->Line);
    }
}
