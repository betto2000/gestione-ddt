<template>
    <div class="app-layout">
      <header class="app-header" v-if="showHeader">
        <div class="header-content">
          <div class="logo">
            <img src="/img/logo.png" alt="B&C Prodotti Chimici" />
          </div>

          <div class="header-actions" v-if="isAuthenticated">
            <button @click="logout" class="logout-btn">
              <i class="fas fa-sign-out-alt"></i>
              <span class="logout-text">Logout</span>
            </button>
          </div>
        </div>
      </header>

      <main class="app-content">
        <slot></slot>
      </main>

      <footer class="app-footer" v-if="showFooter">
        <div class="footer-content">
          <p>&copy; {{ currentYear }} B&C Prodotti Chimici. Tutti i diritti riservati.</p>
        </div>
      </footer>
    </div>
  </template>

  <script>
  import { mapGetters, mapActions } from 'vuex';

  export default {
    props: {
      showHeader: {
        type: Boolean,
        default: true
      },
      showFooter: {
        type: Boolean,
        default: true
      }
    },

    computed: {
      ...mapGetters('auth', ['isAuthenticated']),

      currentYear() {
        return new Date().getFullYear();
      }
    },

    methods: {
      ...mapActions('auth', ['logout']),

      async handleLogout() {
        await this.logout();
        this.$router.push({ name: 'login' });
      }
    }
  };
  </script>

  <style scoped>
  .app-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }

  .app-header {
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 15px 0;
  }

  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
  }

  .logo img {
    height: 40px;
    width: auto;
  }

  .header-actions {
    display: flex;
    align-items: center;
  }

  .logout-btn {
    display: flex;
    align-items: center;
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 14px;
    transition: color 0.3s;
  }

  .logout-btn:hover {
    color: #3490dc;
  }

  .logout-btn i {
    margin-right: 5px;
  }

  .app-content {
    flex: 1;
    padding: 20px 0;
  }

  .app-footer {
    background-color: #f8f9fa;
    padding: 15px 0;
    margin-top: auto;
  }

  .footer-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
    text-align: center;
    color: #6c757d;
    font-size: 14px;
  }

  @media (max-width: 576px) {
    .logout-text {
      display: none;
    }

    .header-content {
      padding: 0 10px;
    }

    .logo img {
      height: 32px;
    }
  }
  </style>
