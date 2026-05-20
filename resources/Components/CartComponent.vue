<template>
<section class="cart">
        
    <!-- Заголовок корзины -->
    <h2>Корзина покупок</h2>
    
    <!-- Состояние: пустая корзина -->
    <div v-if="filteredCartItems.length === 0" class="empty-cart">
      <img src="../assets/icons/noCart.png" alt="Корзина пуста" loading="lazy" >
      <p>Ваша корзина пуста</p>
      
    </div>
    
    <!-- Состояние: корзина с товарами -->
    <div v-else class="cart-with-items">
      <!-- Список товаров -->
      <div class="cart-items">
        <div v-for="item in filteredCartItems" :key="item.id" class="cart-item">
          <div class="item-info">
            <h4>{{ item.name }}</h4>
            <p>{{ item.price }} ₽ × {{ item.quantity }}</p>
          </div>
          
        </div>
      </div>
      
      <!-- Итого -->
      <div class="cart-summary">
        <p>Итого: {{ totalPrice }} ₽</p>
        <button @click="clearRestaurantCart" class="clear-btn">Очистить</button>
        <button @click="goToCartPage" class="checkout-btn">Оформить заказ</button>
      </div>
    </div>
</section>
</template>

<script>
export default {
  name: 'CartComponent',
  props: {
    cartItems: {
      type: Array,
      default: () => []
    },
    // Добавляем пропс для ID текущего ресторана
    currentRestaurantId: {
      type: [Number, String],
      default: null
    }
  },
  computed: {
    // Фильтруем товары по ресторану
    filteredCartItems() {
      if (!this.currentRestaurantId) {
        return this.cartItems;
      }
      return this.cartItems.filter(item => 
        item.restaurantId === Number(this.currentRestaurantId)
      );
    },
    
    totalPrice() {
      return this.filteredCartItems.reduce((total, item) => {
        return total + (item.price * item.quantity);
      }, 0);
    }
  },
  methods: {

    goToCartPage() {
      if (this.$router) {
        this.$router.push('/check');
      }
      this.closeCartPopup();
    },

    handleCheckout() {
      this.$emit('checkout');
    },
    
    // Метод для очистки только текущего ресторана
    clearRestaurantCart() {
      if (!this.currentRestaurantId) {
        // Если нет ID ресторана, очищаем всю корзину
        this.$emit('clear-cart');
        return;
      }
      
      // Очищаем только товары текущего ресторана
      this.$emit('clear-restaurant-cart', Number(this.currentRestaurantId));
    }
  }
}
</script>

<style scoped>
.cart {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
  width: 25%;
  min-height: 600px;
  background-color: white;
  border-radius: 12px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid #e0e0e0;
}

.cart h2 {
  margin: 0 0 20px 0;
  font-size: 20px;
  font-weight: 600;
  color: #333;
  width: 100%;
  text-align: center;
  padding-bottom: 15px;
  border-bottom: 2px solid #f0f0f0;
}

.empty-cart {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 40px 0;
  gap: 30px;
  flex: 1;
}

.empty-cart p {
  text-align: center;
  margin: 0;
  font-size: 18px;
  color: #666;
  font-weight: 500;
}

.cart img {
  width: 240px;
  height: 200px;
  opacity: 0.6;
}

.cart-with-items {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  height: 100%;
  justify-content: space-between;
  flex: 1;
}

.cart-items {
  width: 100%;
  max-height: 400px;
  overflow-y: auto;
  padding: 10px;
  margin-bottom: 20px;
}

.cart-item {
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.cart-item:last-child {
  border-bottom: none;
}

.item-info h4 {
  margin: 0 0 5px 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.item-info p {
  margin: 0;
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.cart-summary {
  width: 100%;
  padding: 20px 0;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 15px;
  border-top: 2px solid #f0f0f0;
}

.cart-summary p {
  margin: 0 0 10px 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.cart-summary button {
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  padding: 12px;
  transition: all 0.2s ease;
  width: 100%;
}

.cart-summary button:hover {
  background-color: #45a049;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.cart-summary button:active {
  transform: translateY(0);
}

.clear-btn {
  background-color: #f44336 !important;
}

.clear-btn:hover {
  background-color: #d32f2f !important;
}
</style>