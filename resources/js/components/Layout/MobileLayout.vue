<template>
    <div class="mobile-layout">
      <header class="mobile-header" v-if="showHeader">
        <div class="logo">
          <img src="/img/logo.png" alt="B&C Prodotti Chimici" />
        </div>
      </header>

      <main class="mobile-content">
        <slot></slot>
      </main>

      <nav class="mobile-nav" v-if="showNav && isAuthenticated">
        <div class="nav-item" :class="{ active: activeRoute === 'scan-qr' }" @click="navigateTo('scan-qr')">
          <i class="fas fa-qrcode"></i>
          <span>Scan</span>
        </div>
        <div class="nav-item" :class="{ active: activeRoute === 'documents' }" @click="navigateTo('documents')">
          <i class="fas fa-file-alt"></i>
          <span>Documenti</span>
        </div>
        <div class="nav-item" @click="logout">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </div>
      </nav>
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
      showNav: {
        type: Boolean,
        default: true
      },
      activeRoute: {
        type: String,
        default: ''
      }
    },

    computed: {
      ...mapGetters('auth', ['isAuthenticated'])
    },

    methods: {
      ...mapActions('auth', ['logout']),

      navigateTo(routeName) {
        if (this.$route.name !== routeName) {
          this.$router.push({ name: routeName });
        }
      },

      async handleLogout() {
        await this.logout();
        this.$router.push({ name: 'login' });
      }
    }
  };
  </script>

  <style scoped>
  .mobile-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background-color: #f8f9fa;
  }

  .mobile-header {
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 15px;
    text-align: center;
  }

  .logo img {
    height: 40px;
    width: auto;
  }

  .mobile-content {
    flex: 1;
    padding: 15px;
    padding-bottom: 70px; /* Spazio per il nav */
  }

  .mobile-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: #fff;
    box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    height: 60px;
  }

  .nav-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    cursor: pointer;
    transition: color 0.3s;
  }

  .nav-item i {
    font-size: 20px;
    margin-bottom: 4px;
  }

  .nav-item span {
    font-size: 12px;
  }

  .nav-item.active {
    color: #3490dc;
  }

  .nav-item:hover {
    color: #3490dc;
  }

  @media (min-width: 768px) {
    .mobile-nav {
      display: none;
    }

    .mobile-content {
      padding-bottom: 15px;
    }
  }
  </style>
