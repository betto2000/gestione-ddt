// resources/js/components/Ddt/SuccessPage.vue

<template>
  <div class="success-page">
    <div class="logo-header">
      <img src="/img/logo.png" alt="B&C Prodotti Chimici" />
    </div>

    <div class="success-container">
      <div class="success-icon">
        <i class="fas fa-check-circle"></i>
      </div>

      <div class="success-message">
        <h2>REGISTRAZIONE EFFETTUATA CORRETTAMENTE</h2>
        <p>Il documento è stato aggiornato con successo.</p>
      </div>
    </div>

    <div class="action-button">
      <button @click="goToHome" class="btn-home">NUOVA SCANSIONE</button>
    </div>
  </div>
</template>

<script>
export default {
  methods: {
    /**
     * Torna alla pagina principale di scansione
     */
    goToHome() {
      console.log("Navigazione a scan-qr");
      this.$router.push({ name: 'scan-qr' });
    }
  }
};
</script>

<style scoped>
.success-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
  text-align: center;
  background-color: #f8f9fa;
}

.logo-header {
  margin-bottom: 40px;
}

.logo-header img {
  max-width: 150px;
  height: auto;
}

.success-container {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 30px;
  margin-bottom: 40px;
  max-width: 400px;
  width: 100%;
}

.success-icon {
  color: #28a745;
  font-size: 80px;
  margin-bottom: 30px;
}

.success-message h2 {
  font-size: 24px;
  font-weight: bold;
  margin: 0 0 15px;
  color: #333;
}

.success-message p {
  font-size: 16px;
  color: #666;
  margin: 0;
}

.action-button {
  margin-top: 20px;
}

.btn-home {
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

.btn-home:hover {
  background-color: #2779bd;
}

@media (max-width: 576px) {
  .success-container {
    padding: 20px;
  }

  .success-icon {
    font-size: 60px;
    margin-bottom: 20px;
  }

  .success-message h2 {
    font-size: 20px;
  }
}
</style>
