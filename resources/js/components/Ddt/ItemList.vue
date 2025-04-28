// resources/js/components/Ddt/ItemsList.vue

<template>
  <div class="items-list">
    <!-- Mostra loader durante il caricamento -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <div class="loading-text">Caricamento in corso...</div>
    </div>

    <!-- Mostra errore se presente -->
    <div v-else-if="error" class="error-message">
      <div class="error-icon">⚠️</div>
      <div>{{ error }}</div>
      <button @click="loadDocumentData" class="retry-button">Riprova</button>
    </div>

    <!-- Contenuto principale -->
    <template v-else-if="document">
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

      <div class="imballi-section">
      <div class="section-title">IMBALLI</div>

      <!-- Select dinamica per il tipo di imballo -->
      <div class="select-container">
        <select v-model="selectedPackageType" class="form-control" :disabled="loadingPackages">
          <option value="" disabled selected>{{ loadingPackages ? 'Caricamento...' : 'Seleziona tipo' }}</option>
          <option v-for="pkg in packages" :key="pkg.Item" :value="pkg.Item">
            {{ pkg.Description }}
          </option>
        </select>
      </div>

        <!-- Lista degli imballi selezionati -->
        <div class="selected-package" v-if="selectedPackageType">
            <div class="package-item">
                <div class="package-name">{{ getPackageName(selectedPackageType) }}</div>
                <div class="package-check" v-if="packageSelected">✓</div>
            </div>
        </div>

        <!-- Sezione quantità -->
        <div class="quantity-section">
          <div class="quantity-row">
            <div class="quantity-label">QUANTITA' RESA</div>
          </div>

          <div class="quantity-input-group">
            <button @click="decreaseQty" class="qty-btn">-</button>
            <div class="qty-input">{{ packageQuantity }}</div>
            <button @click="increaseQty" class="qty-btn">+</button>
          </div>
        </div>
      </div>

      <!-- Lista degli imballi aggiunti -->
    <div class="packages-list" v-if="packages.length > 0">
        <div class="section-title">IMBALLI AGGIUNTI</div>
        <div class="package-list-items">
            <div v-for="(pkg, index) in addedPackages" :key="index" class="package-list-item">
            <div class="package-details">
                <div class="package-type">{{ getPackageName(pkg.type) }}</div>
                <div class="package-qty">{{ pkg.quantity }} pz</div>
            </div>
            <button @click="removePackage(index)" class="remove-btn">&times;</button>
            </div>
        </div>
    </div>

      <!-- Pulsanti azione -->
      <div class="action-buttons">
        <button @click="addPackage" class="btn-add" :disabled="!canAddPackage">
          AGGIUNGI
        </button>
        <button @click="saveAndContinue" class="btn-next" :disabled="localLoading">
            {{ localLoading ? 'SALVATAGGIO...' : 'AVANTI' }}
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
      selectedPackageType: '',
      packageQuantity: 0,
      packages: [],
      addedPackages: [],
      loadingPackages: false,
      saleDocId: null,
      localLoading: false,
      error: null,
      packageSelected: false
    };
  },

  computed: {
    ...mapState('ddt', ['document', 'customer']),
    ...mapGetters('ddt', ['isLoading']),

    loading() {
      return this.isLoading || this.localLoading;
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
    },

    canAddPackage() {
      return this.selectedPackageType && this.packageQuantity > 0;
    },
  },

  created() {
    console.log("ItemsList creato");
    this.initializeComponent();
  },

  methods: {
    ...mapActions('ddt', ['fetchDdtById']),

    initializeComponent() {
      this.saleDocId = this.$route.params.saleDocId;

      console.log("Inizializzazione con saleDocId:", this.saleDocId);

      if (this.saleDocId) {
        this.loadDocumentData();
      } else {
        console.error("SaleDocId mancante");
      }
    },

    async loadDocumentData() {
      this.localLoading = true;
      this.error = null;

      try {
        await this.fetchDdtById({ saleDocId: this.saleDocId });
      } catch (err) {
        console.error("Errore nel caricamento del documento:", err);
        this.error = "Errore nel caricamento del documento";
      } finally {
        this.localLoading = false;
        this.loadPackages();
      }
    },

    async loadPackages() {
      this.loadingPackages = true;

      try {
        const response = await axios.get('/packages');

        if (response.data.success) {
          console.log("Imballi caricati:", response.data.packages);
          this.packages = response.data.packages;
        } else {
          console.error("Errore nel caricamento degli imballi:", response.data.message);
          this.error = "Errore nel caricamento degli imballi";
        }
      } catch (err) {
        console.error("Eccezione nel caricamento degli imballi:", err);
        this.error = "Errore nella comunicazione con il server";
      } finally {
        this.loadingPackages = false;
      }
    },

    getPackageName(packageId) {
      // Cerca l'imballo nell'array e restituisce la descrizione
      const pkg = this.packages.find(p => p.Item === packageId);
      return pkg ? pkg.Description : packageId;
    },

    increaseQty() {
      this.packageQuantity++;
    },

    decreaseQty() {
      if (this.packageQuantity > 0) {
        this.packageQuantity--;
      }
    },

    addPackage() {
        if (this.canAddPackage) {
            // Aggiungi il pacchetto alla lista
            this.addedPackages.push({
            type: this.selectedPackageType,
            quantity: this.packageQuantity
            });

            console.log("Imballo aggiunto:", {
            type: this.selectedPackageType,
            quantity: this.packageQuantity
            });

            // Reset dei campi
            this.selectedPackageType = '';
            this.packageQuantity = 0;
            this.packageSelected = false;
        }
    },

    removePackage(index) {
        this.addedPackages.splice(index, 1);
        console.log("Imballo rimosso, indice:", index);
    },

    async saveAndContinue() {

    this.localLoading = true;

    try {
        // Salva gli imballi al database
        console.log("Salvataggio imballi:", this.addedPackages);

        const response = await axios.post(`/documents/${this.saleDocId}/packages`, {
        packages: this.addedPackages
        });

        if (response.data.success) {
        console.log("Imballi salvati con successo");

        // Navigazione al riepilogo
        this.$router.push({
            name: 'document-summary',
            params: { saleDocId: this.saleDocId }
        });
        } else {
        console.error("Errore nel salvataggio degli imballi:", response.data.message);
        this.error = response.data.message || "Errore nel salvataggio degli imballi";
        }
    } catch (err) {
        console.error("Eccezione nel salvataggio degli imballi:", err);
        this.error = "Errore nella comunicazione con il server";
    } finally {
        this.localLoading = false;
    }
    },

    goToScan() {
      this.$router.push({ name: 'scan-qr' });
    }
  },

  watch: {
    selectedPackageType(newValue) {
      if (newValue) {
        this.packageSelected = true;
      }
    }
  }
};
</script>

<style scoped>
.items-list {
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

.section-title {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 15px;
}

.imballi-section {
  margin-bottom: 20px;
}

.select-container {
  margin-bottom: 15px;
}

.form-control {
  width: 100%;
  padding: 10px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 16px;
  appearance: none;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 16px;
}

.selected-package {
  margin-bottom: 15px;
}

.package-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
  background-color: #f5f5f5;
  border-radius: 4px;
}

.package-check {
  color: #28a745;
  font-size: 18px;
}

.quantity-section {
  margin-bottom: 20px;
}

.quantity-row {
  margin-bottom: 10px;
}

.quantity-label {
  font-size: 14px;
  font-weight: bold;
}

.quantity-input-group {
  display: flex;
  border: 1px solid #ced4da;
  border-radius: 4px;
  overflow: hidden;
}

.qty-btn {
  width: 50px;
  background-color: #f0f0f0;
  border: none;
  font-size: 20px;
  cursor: pointer;
}

.qty-input {
  flex: 1;
  text-align: center;
  padding: 10px;
  font-size: 20px;
}

.packages-list {
  margin-bottom: 20px;
}

.package-list-items {
  background-color: #f5f5f5;
  border-radius: 4px;
  overflow: hidden;
}

.package-list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
  border-bottom: 1px solid #e0e0e0;
}

.package-list-item:last-child {
  border-bottom: none;
}

.package-details {
  display: flex;
  flex-direction: column;
}

.package-type {
  font-weight: bold;
}

.package-qty {
  font-size: 14px;
  color: #666;
}

.remove-btn {
  background: none;
  border: none;
  color: #dc3545;
  font-size: 24px;
  cursor: pointer;
  padding: 0 5px;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.btn-add {
  background-color: #28a745;
  color: white;
  border: none;
  padding: 10px;
  border-radius: 4px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
}

.btn-add:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
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
  margin: 0 auto;
  display: block;
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
  .items-list {
    padding: 15px;
  }
}
</style>
