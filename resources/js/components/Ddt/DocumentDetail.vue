// resources/js/components/Ddt/DocumentDetail.vue

<template>
  <div class="document-detail">
    <!-- Mostra loader durante il caricamento -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <div class="loading-text">Caricamento in corso...</div>
    </div>

    <!-- Mostra errore se presente -->
    <div v-else-if="error" class="error-message">
      <div class="error-icon">⚠️</div>
      <div>{{ error }}</div>
      <button @click="loadDocumentDetail" class="retry-button">Riprova</button>
    </div>

    <!-- Contenuto principale (mostra solo se detail e document esistono) -->
    <template v-else-if="detail && document">
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

      <div class="item-info">
        <div class="info-label">ART.</div>
        <div class="info-value">{{ detail.Item }}</div>
      </div>

      <div class="description">
        {{ detail.Description }}
      </div>

      <div class="quantity-section">
        <div class="quantity-row">
          <div class="quantity-label">QUANTITA'</div>
          <div class="quantity-label">{{ detail.UoM || 'LITRI' }}</div>
        </div>

        <div class="quantity-input">
          <input
            type="number"
            v-model.number="quantity"
            class="form-control"
            step="0.1"
            min="0"
          />
        </div>
      </div>

      <div class="action-button">
        <button @click="saveAndContinue" class="btn-next" :disabled="loading">
          {{ loading ? 'Salvataggio...' : 'AVANTI' }}
        </button>
      </div>
    </template>

    <!-- Fallback se non ci sono dati -->
    <div v-else class="error-message">
      <div>Nessun dato disponibile. Assicurati di aver scansionato un codice QR valido.</div>
      <button @click="goToScan" class="retry-button">Scansiona codice</button>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex';

export default {
  data() {
    return {
      quantity: 0,
      saleDocId: null,
      lineId: null,
      localLoading: false
    };
  },

  computed: {
    ...mapState('ddt', ['document', 'detail', 'customer']),
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

      // Formato: GG/MM/AAAA
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
    console.log("DocumentDetail creato");
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
    },

    // Quando detail cambia, aggiorna la quantità
    detail(newDetail) {
      console.log("Detail cambiato:", newDetail);
      if (newDetail) {
        this.quantity = parseFloat(newDetail.Qty) || 0;
      }
    }
  },

  methods: {
    ...mapActions('ddt', ['fetchDocumentDetail', 'updateQuantity', 'getNextDetail']),

    initializeComponent() {
      this.saleDocId = this.$route.params.saleDocId;
      this.lineId = this.$route.params.lineId;

      console.log("Inizializzazione con params:", {
        saleDocId: this.saleDocId,
        lineId: this.lineId
      });

      if (this.saleDocId) {
        this.loadDocumentDetail();
      } else {
        console.error("SaleDocId mancante");
      }
    },

    async loadDocumentDetail() {
      console.log("Caricamento documento:", this.saleDocId, "linea:", this.lineId);

      if (!this.saleDocId) {
        console.error("SaleDocId non impostato");
        return;
      }

      this.localLoading = true;

      try {
        const response = await this.fetchDocumentDetail({
          saleDocId: this.saleDocId,
          line: this.lineId
        });

        console.log("Risposta caricamento:", response);

        if (response.success) {
          console.log("Caricamento riuscito - Detail:", this.detail);
          console.log("Caricamento riuscito - Document:", this.document);

          // Imposta la quantità iniziale dal dettaglio caricato
          if (this.detail) {
            this.quantity = parseFloat(this.detail.Qty) || 0;
          } else {
            console.error("Detail non impostato dopo il caricamento");
          }
        } else {
          console.error("Errore caricamento:", response);
        }
      } catch (err) {
        console.error("Eccezione nel caricamento dei dettagli:", err);
      } finally {
        this.localLoading = false;
      }
    },

    async saveAndContinue() {
      console.log("Salva e continua - Detail:", this.detail);

      if (!this.detail) {
        console.error("Detail non impostato, impossibile salvare");
        return;
      }

      this.localLoading = true;

      try {
        // Salva la quantità corrente
        const updateResult = await this.updateQuantity({
          sale_doc_id: this.saleDocId,
          line: this.detail.Line,
          item: this.detail.Item,
          quantity: this.quantity
        });

        console.log("Risultato aggiornamento:", updateResult);

        if (!updateResult.success) {
          console.error("Errore nell'aggiornamento della quantità");
          return;
        }

        // Ottieni il prossimo dettaglio, se esiste
        const nextDetail = await this.getNextDetail({
          saleDocId: this.saleDocId,
          currentLine: this.detail.Line
        });

        console.log("Prossimo dettaglio:", nextDetail);

        if (nextDetail) {
          // Vai al prossimo dettaglio
          this.$router.push({
            name: 'document-detail',
            params: {
              saleDocId: this.saleDocId,
              lineId: nextDetail.Line
            }
          });
        } else {
          // Vai alla pagina di riepilogo
                this.$router.push({
                name: 'items-list',  // Assicurati che questa rotta esista nel tuo router
                params: { saleDocId: this.saleDocId }
            });
        }
      } catch (err) {
        console.error("Errore nel salvataggio:", err);
      } finally {
        this.localLoading = false;
      }
    },

    goToScan() {
      this.$router.push({ name: 'scan-qr' });
    }
  }
};
</script>

<style scoped>
.document-detail {
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

.item-info {
  background-color: #f5f5f5;
  padding: 10px 15px;
  margin-bottom: 10px;
  border-radius: 4px;
}

.description {
  background-color: #f5f5f5;
  padding: 10px 15px;
  margin-bottom: 20px;
  border-radius: 4px;
  font-size: 14px;
  min-height: 60px;
}

.quantity-section {
  margin-bottom: 30px;
}

.quantity-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.quantity-label {
  font-size: 14px;
  font-weight: bold;
}

.quantity-input {
  border: 1px solid #ccc;
  border-radius: 4px;
  overflow: hidden;
}

.quantity-input input {
  width: 100%;
  padding: 15px;
  font-size: 22px;
  text-align: center;
  border: none;
  outline: none;
}

.action-button {
  text-align: center;
}

.btn-next {
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

.btn-next:hover:not(:disabled) {
  background-color: #2779bd;
}

.btn-next:disabled {
  background-color: #a0c5e8;
  cursor: not-allowed;
}

@media (max-width: 576px) {
  .document-detail {
    padding: 15px;
  }

  .quantity-input input {
    font-size: 18px;
    padding: 12px;
  }
}
</style>
