<?php
// app/Http/Controllers/DdtController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Services\DdtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DdtController extends Controller
{
    protected $ddtService;

    /**
     * Costruttore con iniezione delle dipendenze
     */
    public function __construct(DdtService $ddtService)
    {
        $this->ddtService = $ddtService;
    }

    /**
     * Ottiene un documento tramite QR code
     */
    public function getDdtByQrCode(Request $request)
    {
        $saleDocId = $request->qr_code;

        Log::info("Tentativo di recupero documento tramite QR code: $saleDocId");

        $document = $this->ddtService->getDocumentWithDetails($saleDocId);

        if (!$document) {
            Log::warning("Documento non trovato per QR code: $saleDocId");
            return response()->json(['message' => 'Documento non trovato'], 404);
        }

        Log::info("Documento trovato per QR code: $saleDocId");
        return response()->json($document);
    }

    /**
     * Ottiene un dettaglio specifico di un documento
     */
    public function getDocumentDetail(Request $request, $saleDocId, $line = null)
    {
            Log::info("Richiesta dettaglio per documento: $saleDocId, Linea: " . ($line ?? 'prima'));

            $detail = $this->ddtService->getDocumentDetail($saleDocId, $line);

            if (!$detail) {
                Log::warning("Dettaglio non trovato: $saleDocId, Linea: " . ($line ?? 'prima'));
                return response()->json(['message' => 'Dettaglio non trovato'], 404);
            }

            Log::info("Dettaglio trovato: $saleDocId, Linea: " . ($detail['detail']->Line ?? 'N/A'));
            return response()->json($detail);
    }

    /**
     * Aggiorna la quantità di un dettaglio
     */
    public function updateQuantity(Request $request)
    {
        $validated = $request->validate([
            'sale_doc_id' => 'required',
            'line' => 'required|integer',
            'quantity' => 'required|numeric|min:0',
        ]);

        Log::info("Tentativo di aggiornamento quantità", $validated);

        $updated = $this->ddtService->updateQuantity(
            $validated['sale_doc_id'],
            $validated['line'],
            $validated['quantity']
        );

        if (!$updated) {
            Log::warning("Aggiornamento quantità fallito", $validated);
            return response()->json(['message' => 'Aggiornamento fallito'], 400);
        }

        Log::info("Quantità aggiornata con successo", $validated);
        return response()->json(['message' => 'Quantità aggiornata con successo']);
    }

    /**
     * Ottiene il prossimo dettaglio
     */
    public function getNextDetail(Request $request, $saleDocId, $currentLine)
    {
        Log::info("Richiesta prossimo dettaglio: $saleDocId, Linea corrente: $currentLine");

        $nextDetail = $this->ddtService->getNextDetailLine($saleDocId, $currentLine);

        if (!$nextDetail) {
            Log::info("Nessun altro dettaglio trovato: $saleDocId, Dopo linea: $currentLine");
            return response()->json(['message' => 'Nessun altro dettaglio trovato'], 404);
        }

        Log::info("Prossimo dettaglio trovato: $saleDocId, Linea: {$nextDetail->Line}");
        return response()->json(['detail' => $nextDetail]);
    }

    /**
     * Conferma un documento
     */
    public function confirmDocument(Request $request, $saleDocId)
    {
        Log::info("Tentativo di conferma documento: $saleDocId");

        $confirmed = $this->ddtService->confirmDocument($saleDocId);

        if (!$confirmed) {
            Log::warning("Conferma documento fallita: $saleDocId");
            return response()->json(['message' => 'Conferma fallita'], 400);
        }

        Log::info("Documento confermato con successo: $saleDocId");
        return response()->json(['message' => 'Documento confermato con successo']);
    }

    /**
     * Ottiene tutti i dettagli di un documento per il riepilogo
     */
    public function getSummary(Request $request, $saleDocId)
    {
        Log::info("Richiesta riepilogo documento: $saleDocId");

        $document = $this->ddtService->getDocumentWithDetails($saleDocId);

        if (!$document) {
            Log::warning("Documento non trovato per riepilogo: $saleDocId");
            return response()->json(['message' => 'Documento non trovato'], 404);
        }

        Log::info("Riepilogo documento trovato: $saleDocId, Dettagli: " . count($document['details']));
        return response()->json($document);
    }
}
