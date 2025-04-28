<template>
    <div class="confirmation">
      <!-- Mostra loader durante il caricamento -->
      <div v-if="loading" class="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Caricamento in corso...</div>
      </div>

      <!-- Mostra errore se presente -->
      <div v-else-if="error" class="error-message">
        <div class="error-icon">⚠️</div>
        <div>{{ error }}</div>
        <button @click="loadDocumentWithDetails" class="retry-button">Riprova</button>
      </div>

      <template v-else-if="document">
      <!-- Intestazione rimane uguale -->
      <div class="logo-header">
        <img src="/img/logo.png" alt="B&C Prodotti Chimici" />
      </div>

      <div class="client-info">
        <div class="info-label">CLIENTE:</div>
        <div class="info-value">{{ customerName }}</div>
      </div>

      <div class="document-info">
        <div class="info-row">
          <div class="info-col">
            <div class="info-label">DDT NR.</div>
            <div class="info-value">{{ docNumber }}</div>
          </div>
          <div class="info-col">
            <div class="info-label">DATA</div>
            <div class="info-value">{{ formattedDate }}</div>
          </div>
        </div>
      </div>

        <div class="summary-title">
          RIEPILOGO
        </div>

        <div class="summary-list">
        <div v-if="loadingRows" class="loading-text">Caricamento righe...</div>
        <div v-else-if="rows.length === 0" class="empty-message">Nessuna riga registrata</div>
        <ul v-else>
          <li v-for="(row, index) in rows" :key="index">
            <div class="detail-item">
              <div class="detail-info">
                <div class="detail-code">{{ row.Item }}</div>
                <div class="detail-description">{{ getItemDescription(row) }}</div>
              </div>
              <div class="detail-quantity">
                {{ formatQuantity(row.Qty) }} {{ row.UoM || '' }}
              </div>
            </div>
          </li>
        </ul>
      </div>

      <div class="action-buttons">
        <!-- Pulsante Annulla -->
        <button @click="cancelAndReturn" class="btn-cancel">
          ANNULLA
        </button>
        <!-- Pulsante Conferma -->
        <button @click="confirmDocument" class="btn-confirm" :disabled="confirmLoading">
          {{ confirmLoading ? 'CONFERMA IN CORSO...' : 'CONFERMA' }}
        </button>
    </div>
      </template>

      <!-- Fallback se non ci sono dati -->
      <div v-else class="error-message">
        <div>Nessun dato disponibile per il riepilogo.</div>
        <button @click="goToScan" class="retry-button">Torna alla scansione</button>
      </div>
    </div>
  </template>

  <script>
  import { mapState, mapGetters, mapActions } from 'vuex';

  export default {
    data() {
      return {
        saleDocId: null,
        confirmLoading: false,
        localLoading: false,
        loadingRows: false,
        rows: [],
        error: null
      };
    },

    computed: {
      ...mapState('ddt', ['document', 'details', 'customer']),
      ...mapGetters('ddt', ['isLoading', 'hasError', 'errorMessage']),

      loading() {
        return this.isLoading || this.localLoading;
      },

      error() {
        return this.errorMessage;
      },

      customerName() {
        return this.customer?.CompanyName || '';
      },

      docNumber() {
        return this.document?.DocNo || '';
      },

      formattedDate() {
        if (!this.document?.DocumentDate) return '';

        try {
          const date = new Date(this.document.DocumentDate);
          const day = date.getDate().toString().padStart(2, '0');
          const month = (date.getMonth() + 1).toString().padStart(2, '0');
          const year = date.getFullYear();

          return `${day}/${month}/${year}`;
        } catch (e) {
          console.error("Errore nella formattazione della data:", e);
          return this.document.DocumentDate || '';
        }
      }
    },

    created() {
      console.log("Confirmation creato");
      this.initializeComponent();
    },

    watch: {
      // Osserva i cambiamenti nei parametri della rotta
      '$route.params': {
        handler(newParams) {
          console.log("Parametri rotta cambiati:", newParams);
          this.initializeComponent();
        },
        deep: true,
        immediate: true
      }
    },

    methods: {
        ...mapActions('ddt', {
            fetchDocumentSummaryAction: 'fetchDocumentSummary',
            confirmDocumentAction: 'confirmDocument'
        }),

      initializeComponent() {
        this.saleDocId = this.$route.params.saleDocId;

        console.log("Inizializzazione con saleDocId:", this.saleDocId);

        if (this.saleDocId) {
          this.loadDocumentWithDetails();
          this.loadRegisteredRows();
        } else {
          console.error("SaleDocId mancante");
        }
      },

      async loadDocumentWithDetails() {
        console.log("Caricamento riepilogo documento:", this.saleDocId);

        if (!this.saleDocId) {
          console.error("SaleDocId non impostato");
          return;
        }

        this.localLoading = true;

        try {
          const response = await this.fetchDocumentSummary({
            saleDocId: this.saleDocId
          });

          console.log("Risposta caricamento:", response);

          if (response.success) {
            console.log("Caricamento riuscito - Document:", this.document);
            console.log("Caricamento riuscito - Details:", this.details);
          } else {
            console.error("Errore caricamento:", response);
          }
        } catch (err) {
          console.error("Eccezione nel caricamento del riepilogo:", err);
        } finally {
          this.localLoading = false;
        }
      },
      async loadRegisteredRows() {
      this.loadingRows = true;

      try {
        // Carica le righe registrate nella tabella DEB_BA4_RigheAutisti
        const response = await axios.get(`/documents/${this.saleDocId}/registered-rows`);

        if (response.data.success) {
          console.log("Righe registrate:", response.data.rows);
          this.rows = response.data.rows;
        } else {
          console.error("Errore nel caricamento delle righe:", response.data.message);
          this.error = "Errore nel caricamento delle righe";
        }
      } catch (err) {
        console.error("Eccezione nel caricamento delle righe:", err);
        this.error = "Errore nella comunicazione con il server";
      } finally {
        this.loadingRows = false;
      }
    },

    getItemDescription(row) {
      // Se è una riga di dettaglio (Line > 0), usa la descrizione dalla tabella MA_SaleDocDetail
      if (row.Line > 0) {
        // Cerca nel dettaglio del documento
        const detail = this.details.find(d => d.Line === row.Line);
        return detail ? detail.Description : row.Item;
      }

      // Se è un imballo (Line = 0), cerca la descrizione nei packages
      return row.Description || row.Item;
    },

    async confirmDocument() {
    console.log("Conferma documento:", this.saleDocId);

    this.confirmLoading = true;

    try {
        const response = await this.confirmDocumentAction({
        saleDocId: this.saleDocId
        });

        console.log("Risposta conferma:", response);

        if (response.success) {
        // Mostra il messaggio di successo
        this.$router.push({ name: 'confirmation-success' });
        } else {
        console.error("Errore conferma:", response);
        this.error = response.message || "Errore durante la conferma";
        }
    } catch (err) {
        console.error("Eccezione nella conferma del documento:", err);
        this.error = "Si è verificato un errore durante la conferma";
    } finally {
        this.confirmLoading = false;
    }
    },

    async cancelAndReturn() {
    console.log("Cancellazione righe e ritorno alla scansione");

    this.localLoading = true;

    try {
        // Cancella tutte le righe registrate per questo documento
        const response = await axios.delete(`/documents/${this.saleDocId}/registered-rows`);

        console.log("Risposta cancellazione:", response.data);

        // Torna alla pagina di scansione QR code
        this.$router.push({ name: 'scan-qr' });
    } catch (err) {
        console.error("Errore nella cancellazione delle righe:", err);
        this.error = "Errore nella cancellazione delle righe";
        this.localLoading = false;
    }
    },

      formatQuantity(qty) {
        if (qty === null || qty === undefined) return '0';

        // Formatta il numero con massimo 3 decimali
        return parseFloat(qty).toFixed(3).replace(/\.?0+$/, '');
      },

      goToScan() {
        this.$router.push({ name: 'scan-qr' });
      }
    }
  };
  </script>

  <style scoped>
  .confirmation {
    padding: 20px;
    max-width: 500px;
    margin: 0 auto;
    position: relative;
    min-height: 80vh;
  }

  .loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 10;
  }

  .loading-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3490dc;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin-bottom: 10px;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .loading-text {
    font-size: 16px;
    color: #333;
  }

  .error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
  }

  .error-icon {
    font-size: 24px;
    margin-bottom: 5px;
  }

  .retry-button {
    background-color: #3490dc;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    margin-top: 10px;
  }

  .logo-header {
    text-align: center;
    margin-bottom: 20px;
  }

  .logo-header img {
    max-width: 120px;
    height: auto;
  }

  .client-info {
    background-color: #f5f5f5;
    padding: 10px 15px;
    margin-bottom: 10px;
    border-radius: 4px;
  }

  .document-info {
    margin-bottom: 20px;
  }

  .info-row {
    display: flex;
    justify-content: space-between;
  }

  .info-col {
    flex: 1;
    padding: 10px 15px;
    background-color: #f5f5f5;
    border-radius: 4px;
  }

  .info-col:first-child {
    margin-right: 10px;
  }

  .info-label {
    font-size: 12px;
    font-weight: bold;
    color: #666;
    margin-bottom: 5px;
  }

  .info-value {
    font-size: 16px;
    font-weight: bold;
  }

  .summary-title {
    margin: 20px 0 15px;
    font-size: 16px;
    font-weight: bold;
    text-align: center;
  }

  .summary-list {
    background-color: #f5f5f5;
    padding: 15px;
    margin-bottom: 30px;
    border-radius: 4px;
  }

  .summary-list ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .summary-list li {
    padding: 8px 0;
    border-bottom: 1px solid #ddd;
  }

  .summary-list li:last-child {
    border-bottom: none;
  }

  .detail-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }

  .detail-info {
    flex: 1;
    margin-right: 10px;
  }

  .detail-code {
    font-weight: bold;
    margin-bottom: 2px;
  }

  .detail-description {
    font-size: 14px;
    color: #666;
  }

  .detail-quantity {
    font-weight: bold;
    min-width: 60px;
    text-align: right;
  }

  .action-button {
    text-align: center;
    margin-top: 30px;
  }

  .btn-confirm {
    background-color: #3490dc;
    color: white;
    border: none;
    padding: 12px 40px;
    border-radius: 25px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s;
  }

  .btn-confirm:hover:not(:disabled) {
    background-color: #2779bd;
  }

  .btn-confirm:disabled {
    background-color: #a0c5e8;
    cursor: not-allowed;
  }

  .action-buttons {
  display: flex;
  justify-content: space-between;
  margin-top: 30px;
}

.btn-cancel {
  background-color: #dc3545;
  color: white;
  border: none;
  padding: 12px 30px;
  border-radius: 25px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.3s;
}

.btn-cancel:hover {
  background-color: #c82333;
}

.loading-text, .empty-message {
  text-align: center;
  padding: 20px;
  color: #6c757d;
}
  </style>
