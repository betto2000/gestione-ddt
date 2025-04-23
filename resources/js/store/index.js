import Vue from 'vue';
import Vuex from 'vuex';
import auth from './modules/auth';
import ddt from './modules/ddt';

Vue.use(Vuex);

const store = new Vuex.Store({
  modules: {
    auth,
    ddt
  }
});

export default store;
