<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DdtService;

class ApiController extends Controller
{
    protected $ddtService;
    
    public function __construct(DdtService $ddtService)
    {
        $this->ddtService = $ddtService;
    }
    
    public function getDocumentTypes()
    {
        // API per ottenere tipi di documento (se necessario)
        return response()->json(['types' => ['DDT', 'Fattura', 'Nota di Credito']]);
    }
    
    public function getCustomers()
    {
        $customers = $this->ddtService->getAllCustomers();
        return response()->json(['customers' => $customers]);
    }
}