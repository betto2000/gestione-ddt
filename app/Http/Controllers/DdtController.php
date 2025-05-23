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
            'quantity' => 'required|numeric',
            'item' => 'required|string',  // Aggiungiamo l'item dalla riga di dettaglio
        ]);

        \Log::info("Registrazione quantità", $validated);

        // Elimina eventuali imballi esistenti per questo documento
        DB::delete('DELETE FROM DEB_BA4_RigheAutisti WHERE SaleDocId = ? AND Line = ?', [$validated['sale_doc_id'],$validated['line']]);

        try {

            DB::insert(
                'INSERT INTO DEB_BA4_RigheAutisti (SaleDocId, Line, Item, Qty, Aggiornato) VALUES (?, ?, ?, ?, ?)',
                [
                    $validated['sale_doc_id'],
                    $validated['line'],
                    $validated['item'],
                    $validated['quantity'],
                    '0'
                ]
            );

            \Log::info("Quantità registrata con successo");

            return response()->json([
                'success' => true,
                'message' => 'Quantità registrata con successo'
            ]);
        } catch (\Exception $e) {
            \Log::error("Errore nella registrazione della quantità: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore nella registrazione della quantità'
            ], 500);
        }
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

    /**
     * Salva gli imballi associati al documento
     */
    public function savePackages(Request $request, $saleDocId)
    {
        $packages = $request->validate([
            'packages' => 'required|array',
            'packages.*.type' => 'required|string',
            'packages.*.quantity' => 'required|numeric|min:0'
        ]);

        \Log::info("Salvataggio imballi per documento: $saleDocId", ['count' => count($request->packages)]);

        try {
            // Elimina eventuali imballi esistenti per questo documento
            DB::delete('DELETE FROM DEB_BA4_RigheAutisti WHERE SaleDocId = ? AND Line = 0', [$saleDocId]);

            // Inserisci i nuovi imballi
            foreach ($request->packages as $package) {

                DB::insert(
                    'INSERT INTO DEB_BA4_RigheAutisti (SaleDocId, Line, Item, Qty,Aggiornato) VALUES (?, ?, ?, ?, ?)',
                    [
                        $saleDocId,
                        0, // Line sempre 0 come richiesto
                        $package['type'],
                        $package['quantity'],
                        '0'
                    ]
                );
            }

            \Log::info("Imballi salvati con successo");

            return response()->json([
                'success' => true,
                'message' => 'Imballi salvati con successo'
            ]);
        } catch (\Exception $e) {
            \Log::error("Errore nel salvataggio degli imballi: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore nel salvataggio degli imballi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottiene le righe registrate nella tabella DEB_BA4_RigheAutisti
     */
    public function getRegisteredRows($saleDocId)
    {
        \Log::info("Richiesta righe registrate per documento: $saleDocId");

        try {
            // Ottieni le righe dalla tabella DEB_BA4_RigheAutisti
            $rows = DB::select("
                SELECT r.SaleDocId, r.Line, r.Item, r.Qty,
                    CASE
                        WHEN r.Line > 0 THEN d.Description
                        WHEN r.Line = 0 THEN i.Description
                        ELSE NULL
                    END as Description,
                    CASE
                       WHEN r.Line > 0 THEN d.UoM
                       WHEN r.Line = 0 THEN i.BaseUoM
                       ELSE NULL
                    END as UoM
                FROM DEB_BA4_RigheAutisti r
                LEFT JOIN MA_SaleDocDetail d ON r.SaleDocId = d.SaleDocId AND r.Line = d.Line
                LEFT JOIN MA_Items i ON r.Item = i.Item
                WHERE r.SaleDocId = ?
                ORDER BY r.Line, r.Item
            ", [$saleDocId]);

            \Log::info("Righe registrate trovate: " . count($rows));

            return response()->json([
                'success' => true,
                'rows' => $rows
            ]);
        } catch (\Exception $e) {
            \Log::error("Errore nel recupero delle righe registrate: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero delle righe registrate'
            ], 500);
        }
    }

    /**
     * Cancella le righe registrate per un documento
     */
    public function deleteRegisteredRows($saleDocId)
    {
        \Log::info("Richiesta di cancellazione righe per documento: $saleDocId");

        try {
            // Elimina tutte le righe per questo SaleDocId
            $count = DB::delete('DELETE FROM DEB_BA4_RigheAutisti WHERE SaleDocId = ?', [$saleDocId]);

            \Log::info("Righe cancellate: $count");

            return response()->json([
                'success' => true,
                'message' => 'Righe cancellate con successo',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            \Log::error("Errore nella cancellazione delle righe: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore nella cancellazione delle righe'
            ], 500);
        }
    }
}
