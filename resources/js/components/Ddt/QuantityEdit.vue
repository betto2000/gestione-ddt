<template>
    <div class="quantity-edit">
      <div class="quantity-header">
        <div class="product-info">
          <div class="product-code">{{ item.Item }}</div>
          <div class="product-description">{{ item.Description }}</div>
        </div>
      </div>

      <div class="quantity-container">
        <div class="quantity-labels">
          <div class="quantity-label">QUANTITA'</div>
          <div class="quantity-label">{{ item.UoM || 'LITRI' }}</div>
        </div>

        <div class="quantity-input-group">
          <button @click="decreaseQuantity" class="quantity-btn">-</button>
          <input
            type="number"
            v-model.number="quantity"
            class="quantity-input"
            step="0.1"
            min="0"
            @change="validateQuantity"
          />
          <button @click="increaseQuantity" class="quantity-btn">+</button>
        </div>
      </div>

      <div class="quantity-actions">
        <button @click="resetQuantity" class="reset-btn">Reset</button>
        <button @click="saveQuantity" class="save-btn">Conferma</button>
      </div>
    </div>
  </template>

  <script>
  export default {
    props: {
      item: {
        type: Object,
        required: true
      },
      initialQuantity: {
        type: Number,
        default: 0
      }
    },

    data() {
      return {
        quantity: this.initialQuantity,
        originalQuantity: this.initialQuantity
      };
    },

    watch: {
      initialQuantity(newVal) {
        this.quantity = newVal;
        this.originalQuantity = newVal;
      }
    },

    methods: {
      increaseQuantity() {
        // Incrementa con step di 0.001 per liquidi/misure precise
        this.quantity = parseFloat((this.quantity + 0.001).toFixed(3));
      },

      decreaseQuantity() {
        if (this.quantity > 0) {
          // Decrementa con step di 0.001
          this.quantity = parseFloat((this.quantity - 0.001).toFixed(3));
        }
      },

      validateQuantity() {
        // Assicura che la quantità non sia negativa
        if (this.quantity < 0) {
          this.quantity = 0;
        }

        // Arrotonda a 3 decimali
        this.quantity = parseFloat(this.quantity.toFixed(3));
      },

      resetQuantity() {
        this.quantity = this.originalQuantity;
      },

      saveQuantity() {
        this.$emit('update', {
          item: this.item,
          quantity: this.quantity
        });
      }
    }
  };
  </script>

  <style scoped>
  .quantity-edit {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
  }

  .quantity-header {
    margin-bottom: 20px;
  }

  .product-code {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 5px;
  }

  .product-description {
    color: #666;
    font-size: 14px;
  }

  .quantity-container {
    margin-bottom: 20px;
  }

  .quantity-labels {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
  }

  .quantity-label {
    font-weight: bold;
    font-size: 14px;
  }

  .quantity-input-group {
    display: flex;
    border: 1px solid #ced4da;
    border-radius: 4px;
    overflow: hidden;
  }

  .quantity-btn {
    width: 50px;
    background-color: #f0f0f0;
    border: none;
    font-size: 18px;
    cursor: pointer;
  }

  .quantity-input {
    flex: 1;
    padding: 10px;
    text-align: center;
    font-size: 18px;
    border: none;
    outline: none;
  }

  .quantity-actions {
    display: flex;
    justify-content: space-between;
  }

  .reset-btn, .save-btn {
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
  }

  .reset-btn {
    background-color: #f8f9fa;
    color: #333;
    border: 1px solid #ced4da;
  }

  .save-btn {
    background-color: #3490dc;
    color: white;
    border: none;
  }

  @media (max-width: 576px) {
    .quantity-edit {
      padding: 15px;
    }

    .quantity-btn {
      width: 40px;
    }

    .quantity-input {
      font-size: 16px;
    }

    .reset-btn, .save-btn {
      padding: 8px 15px;
      font-size: 14px;
    }
  }
  </style>
