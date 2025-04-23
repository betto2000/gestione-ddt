<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDoc extends Model
{
    protected $table = 'MA_SaleDoc';
    protected $primaryKey = 'SaleDocId';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'SaleDocId', 'DocNo', 'DocumentDate', 'CustSupp', 'CustSuppType'
    ];

    public function details()
    {
        return $this->hasMany(SaleDocDetail::class, 'SaleDocId', 'SaleDocId');
    }

    public function customer()
    {
        return $this->belongsTo(CustSupp::class, 'CustSupp', 'CustSupp')
            ->where('CustSuppType', $this->CustSuppType);
    }
}
