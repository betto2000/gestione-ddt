<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustSupp extends Model
{
    protected $table = 'MA_CustSupp';
    protected $primaryKey = ['CustSuppType', 'CustSupp'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'CustSuppType', 'CustSupp', 'CompanyName'
    ];

    public function saleDocs()
    {
        return $this->hasMany(SaleDoc::class, 'CustSupp', 'CustSupp')
            ->where('CustSuppType', $this->CustSuppType);
    }

    // Necessario per chiavi primarie composte in Laravel
    protected function setKeysForSaveQuery($query)
    {
        return $query->where('CustSuppType', $this->CustSuppType)
                     ->where('CustSupp', $this->CustSupp);
    }
}