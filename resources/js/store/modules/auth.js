import axios from 'axios';

const state = {
    user: null,
    token: localStorage.getItem('device_token') || null,
    deviceId: localStorage.getItem('device_id') || null,
    isAuthenticated: false,
    isDeviceCertified: false
  };

const mutations = {
    SET_USER(state, user) {
      state.user = user;
      state.isAuthenticated = true;
    },

    SET_TOKEN(state, token) {
      state.token = token;
    },

    SET_DEVICE_ID(state, deviceId) {
      state.deviceId = deviceId;
    },

    SET_DEVICE_CERTIFIED(state, isCertified) {
      state.isDeviceCertified = isCertified;
    }
};

const getters = {
  isAuthenticated: state => !!state.token && !!state.user,
  currentUser: state => state.user,
  isDeviceCertified: state => state.isDeviceCertified
};

const actions = {
  async loginUser({ commit }, credentials) {
    try {
        const response = await axios.post('/login', credentials);

      if (response.data.token) {
        // Salva token e device_id
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('device_id', credentials.device_id);

        // Imposta l'header predefinito per le richieste successive
        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
        axios.defaults.headers.common['Device-ID'] = credentials.device_id;
        axios.defaults.headers.common['Device-Token'] = response.data.token;

        commit('SET_USER', response.data.user);
        commit('SET_TOKEN', response.data.token);
        commit('SET_DEVICE_ID', credentials.device_id);
        commit('SET_DEVICE_CERTIFIED', response.data.device_certified);

        return {
          success: true,
          device_certified: response.data.device_certified
        };
      }

      return { success: false, message: 'Login fallito' };
    } catch (error) {
      console.error('Errore login:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Errore durante l\'accesso'
      };
    }
  },

  async certifyDevice({ commit }, { device_id }) {
    try {
      const response = await axios.post('/certify-device', { device_id });

      if (response.data) {
        commit('SET_DEVICE_CERTIFIED', true);
        return { success: true };
      }

      return { success: false };
    } catch (error) {
      console.error('Errore certificazione dispositivo:', error);
      return { success: false };
    }
  },

  async logout({ commit }) {
    try {
      await axios.post('/logout', {
        device_id: state.deviceId
      });
    } catch (error) {
      console.error('Errore logout:', error);
    }

    // Rimuovi token e device_id in ogni caso
    localStorage.removeItem('token');

    // Rimuovi gli header
    delete axios.defaults.headers.common['Authorization'];
    delete axios.defaults.headers.common['Device-Token'];

    commit('LOGOUT');

    return { success: true };
  },

  checkAuth({ commit, state }) {
    const deviceId = localStorage.getItem('device_id');
    const token = localStorage.getItem('device_token');

    if (deviceId && token) {
      // Imposta headers per axios
      axios.defaults.headers.common['Device-ID'] = deviceId;
      axios.defaults.headers.common['Device-Token'] = token;

      // Tenta di recuperare l'utente
      return axios.get('/check-device')
        .then(response => {
          if (response.data.authenticated) {
            commit('SET_USER', response.data.user);
            commit('SET_TOKEN', token);
            commit('SET_DEVICE_ID', deviceId);
            commit('SET_DEVICE_CERTIFIED', true);
            return true;
          }
          return false;
        })
        .catch(() => false);
    }

    return Promise.resolve(false);
  }
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
};
