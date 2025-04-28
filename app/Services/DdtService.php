<?php
// app/Services/DdtService.php

namespace App\Services;

use App\Models\SaleDoc;
use App\Models\SaleDocDetail;
use App\Models\CustSupp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DdtService
{
    /**
     * Ottiene un documento con i suoi dettagli
     *
     * @param string $saleDocId
     * @return array|null
     */
    public function getDocumentWithDetails($saleDocId)
    {
        try {
            // Ottieni il documento principale
            $document = DB::table('MA_SaleDoc')
                ->where('SaleDocId', $saleDocId)
                ->first();

            if (!$document) {
                Log::warning("Documento non trovato: $saleDocId");
                return null;
            }

            // Ottieni i dettagli del documento
            $details = DB::table('MA_SaleDocDetail')
                ->where('SaleDocId', $saleDocId)
                ->orderBy('Line', 'asc')
                ->get();

            // Ottieni il cliente
            $customer = null;
            if (isset($document->CustSupp) && isset($document->CustSuppType)) {
                $customer = DB::table('MA_CustSupp')
                    ->where('CustSupp', $document->CustSupp)
                    ->where('CustSuppType', $document->CustSuppType)
                    ->first();
            }

            Log::info("Documento caricato: $saleDocId", [
                'details_count' => $details->count(),
                'has_customer' => $customer ? 'sì' : 'no'
            ]);

            return [
                'document' => $document,
                'details' => $details,
                'customer' => $customer,
            ];
        } catch (\Exception $e) {
            Log::error("Errore nel caricamento del documento: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Ottiene un dettaglio specifico del documento
     *
     * @param string $saleDocId
     * @param int|null $line
     * @return array|null
     */
    public function getDocumentDetail($saleDocId, $line = null)
    {
        try {
            // Query base per i dettagli
            $query = DB::table('MA_SaleDocDetail')
                ->where('SaleDocId', $saleDocId);

            // Se la linea è specificata, filtra per quella linea
            if ($line !== null) {
                $detail = $query->where('Line', $line)->first();
            } else {
                // Altrimenti prendi il primo dettaglio
                $detail = $query->orderBy('Line', 'asc')->first();
            }

            if (!$detail) {
                Log::warning("Dettaglio non trovato: $saleDocId, Line: $line");
                return null;
            }

            // Ottieni il documento principale
            $document = DB::table('MA_SaleDoc')
                ->where('SaleDocId', $saleDocId)
                ->first();

            // Ottieni il cliente
            $customer = null;
            if ($document && isset($document->CustSupp) && isset($document->CustSuppType)) {
                $customer = DB::table('MA_CustSupp')
                    ->where('CustSupp', $document->CustSupp)
                    ->where('CustSuppType', $document->CustSuppType)
                    ->first();
            }

            Log::info("Dettaglio caricato: $saleDocId, Line: {$detail->Line}");

            return [
                'detail' => $detail,
                'document' => $document,
                'customer' => $customer,
            ];
        } catch (\Exception $e) {
            Log::error("Errore nel caricamento del dettaglio: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Conferma un documento
     *
     * @param string $saleDocId
     * @return bool
     */
    public function confirmDocument($saleDocId)
    {
        try {
            // Qui puoi aggiungere logica di business per la conferma del documento
            // Ad esempio, impostare un flag di stato, registrare la data di conferma, ecc.

            Log::info("Documento confermato: $saleDocId");
            return true;
        } catch (\Exception $e) {
            Log::error("Errore nella conferma del documento: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Ottiene il prossimo dettaglio in ordine di linea
     *
     * @param string $saleDocId
     * @param int $currentLine
     * @return object|null
     */
    public function getNextDetailLine($saleDocId, $currentLine)
    {
        try {
            $nextDetail = DB::table('MA_SaleDocDetail')
                ->where('SaleDocId', $saleDocId)
                ->where('Line', '>', $currentLine)
                ->orderBy('Line', 'asc')
                ->first();

            if ($nextDetail) {
                Log::info("Prossimo dettaglio trovato: $saleDocId, Line: {$nextDetail->Line}");
            } else {
                Log::info("Nessun altro dettaglio dopo la linea $currentLine");
            }

            return $nextDetail;
        } catch (\Exception $e) {
            Log::error("Errore nel recupero del prossimo dettaglio: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Ottiene tutti i dettagli di un documento
     *
     * @param string $saleDocId
     * @return \Illuminate\Support\Collection|null
     */
    public function getAllDetails($saleDocId)
    {
        try {
            $details = DB::table('MA_SaleDocDetail')
                ->where('SaleDocId', $saleDocId)
                ->orderBy('Line', 'asc')
                ->get();

            Log::info("Dettagli caricati: $saleDocId, Count: {$details->count()}");
            return $details;
        } catch (\Exception $e) {
            Log::error("Errore nel caricamento di tutti i dettagli: {$e->getMessage()}");
            return null;
        }
    }
}
