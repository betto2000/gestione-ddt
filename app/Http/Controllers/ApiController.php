<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DdtService;
use Illuminate\Support\Facades\DB;

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

    public function getPackages()
    {
        try {
            // Preleva gli imballi dal database
            $packages = DB::table('MA_Items')
                ->select('Item', 'Description')
                ->where('EX_IsPackage', '1')
                ->orderBy('Item')
                ->get();

            \Log::info("Imballi recuperati: " . $packages->count());

            return response()->json([
                'success' => true,
                'packages' => $packages
            ]);
        } catch (\Exception $e) {
            \Log::error("Errore nel recupero degli imballi: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero degli imballi'
            ], 500);
        }
    }
}
