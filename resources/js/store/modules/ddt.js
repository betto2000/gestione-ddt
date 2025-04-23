// resources/js/store/modules/ddt.js

import axios from 'axios';

const state = {
  document: null,
  details: [],
  detail: null,
  customer: null,
  loading: false,
  error: null
};

const getters = {
  isLoading: state => state.loading,
  hasError: state => !!state.error,
  errorMessage: state => state.error,
  document: state => state.document,
  details: state => state.details,
  currentDetail: state => state.detail,
  customer: state => state.customer
};

const actions = {
  /**
   * Recupera un documento tramite QR code
   */
  async fetchDdtByQrCode({ commit }, { qr_code }) {
    console.log("Chiamata fetchDdtByQrCode con:", qr_code);
    commit('SET_LOADING', true);
    commit('CLEAR_ERROR');

    try {
      console.log("Invio richiesta a /scan-qr");
      const response = await axios.post('/scan-qr', { qr_code });
      console.log("Risposta ricevuta da /scan-qr:", response.data);

      if (response.data) {
        commit('SET_DOCUMENT', response.data.document);
        commit('SET_DETAILS', response.data.details || []);
        commit('SET_CUSTOMER', response.data.customer);

        return {
          success: true,
          document: response.data.document,
          details: response.data.details || []
        };
      }

      commit('SET_ERROR', 'Documento non trovato');
      return { success: false, message: 'Documento non trovato' };
    } catch (error) {
      console.error("Errore in fetchDdtByQrCode:", error);
      console.error("Risposta errore:", error.response?.data);

      const message = error.response?.data?.message || 'Errore durante la scansione del QR code';
      commit('SET_ERROR', message);
      return { success: false, message };
    } finally {
      commit('SET_LOADING', false);
    }
  },

  /**
   * Recupera un documento con i suoi dettagli
   */
  async fetchDdtById({ commit }, { saleDocId }) {
    console.log("Chiamata fetchDdtById con:", saleDocId);
    commit('SET_LOADING', true);
    commit('CLEAR_ERROR');

    try {
      console.log(`Invio richiesta a /documents/${saleDocId}`);
      const response = await axios.get(`/documents/${saleDocId}`);
      axios.interceptors.response.use(
        response => {
          console.log('Risposta API ricevuta:', response.config.url, response.data);
          return response;
        },
        error => {
          console.error('Errore API:', error.config.url, error.response?.status, error.message);
          return Promise.reject(error);
        }
      );
      console.log(`Risposta ricevuta da /documents/${saleDocId}:`, response.data);

      if (response.data) {
        commit('SET_DOCUMENT', response.data.document);
        commit('SET_DETAILS', response.data.details || []);
        commit('SET_CUSTOMER', response.data.customer);

        return { success: true };
      }

      commit('SET_ERROR', 'Documento non trovato');
      return { success: false };
    } catch (error) {
      console.error("Errore in fetchDdtById:", error);
      console.error("Risposta errore:", error.response?.data);

      commit('SET_ERROR', 'Errore durante il caricamento del documento');
      return { success: false };
    } finally {
      commit('SET_LOADING', false);
    }
  },

  /**
   * Recupera un dettaglio specifico del documento
   */
  async fetchDocumentDetail({ commit }, { saleDocId, line }) {
    console.log("Chiamata fetchDocumentDetail con:", { saleDocId, line });
    commit('SET_LOADING', true);
    commit('CLEAR_ERROR');

    try {
      let url = `/documents/${saleDocId}`;
      if (line !== undefined && line !== null) {
        url += `/details/${line}`;
      } else {
        url += '/details';
      }

      console.log(`Invio richiesta a ${url}`);
      const response = await axios.get(url);
      axios.interceptors.response.use(
        response => {
          console.log('Risposta API ricevuta:', response.config.url, response.data);
          return response;
        },
        error => {
          console.error('Errore API:', error.config.url, error.response?.status, error.message);
          return Promise.reject(error);
        }
      );
      console.log(`Risposta ricevuta da ${url}:`, response.data);

      if (response.data) {
        if (response.data.document) {
          commit('SET_DOCUMENT', response.data.document);
        }

        if (response.data.detail) {
          commit('SET_DETAIL', response.data.detail);
        } else {
          console.error("Dettaglio mancante nella risposta:", response.data);
          commit('SET_ERROR', 'Dettaglio non trovato nella risposta');
          return { success: false };
        }

        if (response.data.customer) {
          commit('SET_CUSTOMER', response.data.customer);
        }

        return { success: true };
      }

      commit('SET_ERROR', 'Dettaglio non trovato');
      return { success: false };
    } catch (error) {
      console.error("Errore in fetchDocumentDetail:", error);
      console.error("Risposta errore:", error.response?.data);

      commit('SET_ERROR', 'Errore durante il caricamento del dettaglio');
      return { success: false };
    } finally {
      commit('SET_LOADING', false);
    }
  },

  /**
   * Aggiorna la quantità di un dettaglio
   */
  async updateQuantity({ commit }, { sale_doc_id, line, quantity }) {
    console.log("Chiamata updateQuantity con:", { sale_doc_id, line, quantity });
    commit('SET_LOADING', true);
    commit('CLEAR_ERROR');

    try {
      console.log("Invio richiesta a /documents/update-quantity");
      const response = await axios.post('/documents/update-quantity', {
        sale_doc_id,
        line,
        quantity
      });
      console.log("Risposta ricevuta da /documents/update-quantity:", response.data);

      if (response.data) {
        // Aggiorna la quantità nel dettaglio corrente
        commit('UPDATE_DETAIL_QUANTITY', { line, quantity });

        return { success: true };
      }

      commit('SET_ERROR', 'Aggiornamento fallito');
      return { success: false };
    } catch (error) {
      console.error("Errore in updateQuantity:", error);
      console.error("Risposta errore:", error.response?.data);

      commit('SET_ERROR', 'Errore durante l\'aggiornamento della quantità');
      return { success: false };
    } finally {
      commit('SET_LOADING', false);
    }
  },

  /**
   * Recupera il prossimo dettaglio
   */
  async getNextDetail({ commit }, { saleDocId, currentLine }) {
    console.log("Chiamata getNextDetail con:", { saleDocId, currentLine });
    commit('SET_LOADING', true);

    try {
      console.log(`Invio richiesta a /documents/${saleDocId}/next-detail/${currentLine}`);
      const response = await axios.get(`/documents/${saleDocId}/next-detail/${currentLine}`);
      console.log(`Risposta ricevuta da /documents/${saleDocId}/next-detail/${currentLine}:`, response.data);

      if (response.data && response.data.detail) {
        return response.data.detail;
      }

      console.log("Nessun altro dettaglio trovato");
      return null;
    } catch (error) {
      console.error("Errore in getNextDetail:", error);
      console.error("Risposta errore:", error.response?.data);

      // Se riceviamo un 404, significa che non ci sono più dettagli (non è un errore)
      if (error.response && error.response.status === 404) {
        console.log("Nessun altro dettaglio (404)");
        return null;
      }

      commit('SET_ERROR', 'Errore durante il recupero del prossimo dettaglio');
      return null;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  /**
   * Recupera il riepilogo del documento
   */
  async fetchDocumentSummary({ commit }, { saleDocId }) {
    console.log("Chiamata fetchDocumentSummary con:", saleDocId);
    commit('SET_LOADING', true);
    commit('CLEAR_ERROR');

    try {
      console.log(`Invio richiesta a /documents/${saleDocId}/summary`);
      const response = await axios.get(`/documents/${saleDocId}/summary`);
      console.log(`Risposta ricevuta da /documents/${saleDocId}/summary:`, response.data);

      if (response.data) {
        commit('SET_DOCUMENT', response.data.document);
        commit('SET_DETAILS', response.data.details || []);
        commit('SET_CUSTOMER', response.data.customer);

        return { success: true };
      }

      commit('SET_ERROR', 'Riepilogo non trovato');
      return { success: false };
    } catch (error) {
      console.error("Errore in fetchDocumentSummary:", error);
      console.error("Risposta errore:", error.response?.data);

      commit('SET_ERROR', 'Errore durante il caricamento del riepilogo');
      return { success: false };
    } finally {
      commit('SET_LOADING', false);
    }
  },

  /**
   * Conferma il documento
   */
  async confirmDocument({ commit }, { saleDocId }) {
    console.log("Chiamata confirmDocument con:", saleDocId);
    commit('SET_LOADING', true);
    commit('CLEAR_ERROR');

    try {
      console.log(`Invio richiesta a /documents/${saleDocId}/confirm`);
      const response = await axios.post(`/documents/${saleDocId}/confirm`);
      console.log(`Risposta ricevuta da /documents/${saleDocId}/confirm:`, response.data);

      if (response.data) {
        return { success: true };
      }

      commit('SET_ERROR', 'Conferma fallita');
      return { success: false };
    } catch (error) {
      console.error("Errore in confirmDocument:", error);
      console.error("Risposta errore:", error.response?.data);

      commit('SET_ERROR', 'Errore durante la conferma del documento');
      return { success: false };
    } finally {
      commit('SET_LOADING', false);
    }
  }
};

const mutations = {
  SET_LOADING(state, isLoading) {
    state.loading = isLoading;
  },

  SET_ERROR(state, error) {
    state.error = error;
  },

  CLEAR_ERROR(state) {
    state.error = null;
  },

  SET_DOCUMENT(state, document) {
    console.log("Mutation SET_DOCUMENT:", document);
    state.document = document;
  },

  SET_DETAILS(state, details) {
    console.log("Mutation SET_DETAILS:", details);
    state.details = details;
  },

  SET_DETAIL(state, detail) {
    console.log("Mutation SET_DETAIL:", detail);
    state.detail = detail;
  },

  SET_CUSTOMER(state, customer) {
    console.log("Mutation SET_CUSTOMER:", customer);
    state.customer = customer;
  },

  UPDATE_DETAIL_QUANTITY(state, { line, quantity }) {
    console.log("Mutation UPDATE_DETAIL_QUANTITY:", { line, quantity });

    if (state.detail && state.detail.Line === line) {
      state.detail.Qty = quantity;
    }

    if (state.details && state.details.length > 0) {
      const index = state.details.findIndex(d => d.Line === line);
      if (index !== -1) {
        state.details[index].Qty = quantity;
      }
    }
  },

  RESET_STATE(state) {
    state.document = null;
    state.details = [];
    state.detail = null;
    state.customer = null;
    state.error = null;
  }
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
};
