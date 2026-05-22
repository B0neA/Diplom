<template>
  <section class="cart">
    <h2>Корзина покупок</h2>
    
    <div v-if="!filtered.length" class="empty-cart">
      <!-- Иконка из БД с заглушкой -->
      <img 
        :src="cartEmptyIcon" 
        alt="Корзина пуста" 
        loading="lazy" 
        class="empty-cart-icon"
        @error="onIconError"
      />
      <p>Ваша корзина пуста</p>
    </div>
    
    <div v-else class="cart-with-items">
      <div class="cart-items">
        <div v-for="item in filtered" :key="item.id" class="cart-item">
          <div class="item-info">
            <h4>{{ item.name }}</h4>
            <p>{{ item.price }} ₽ × {{ item.quantity }}</p>
          </div>
        </div>
      </div>
      
      <div class="cart-summary">
        <p>Итого: {{ total }} ₽</p>
        <button @click="clearRestaurantCart" class="clear-btn">Очистить</button>
        <button @click="goToCheckout" class="checkout-btn">Оформить заказ</button>
      </div>
    </div>
  </section>
</template>

<script>
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://cuibxmcjdkgjffmmzwgd.supabase.co/rest/v1',
  headers: {
    apikey: 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
    Authorization: 'Bearer sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
  },
});

export default {
  name: 'CartComponent',
  props: {
    cartItems: { type: Array, default: () => [] },
    currentRestaurantId: { type: [Number, String], default: null },
  },
  emits: ['clear-cart', 'clear-restaurant-cart'],
  
  data() {
    return {
      cartEmptyIcon: '',
    };
  },

  computed: {
    id() { return Number(this.currentRestaurantId); },
    filtered() {
      return !this.currentRestaurantId 
        ? this.cartItems
        : this.cartItems.filter(i => (i.restaurantId || i.restaurant_id) == this.id);
    },
    total() { 
      return this.filtered.reduce((t, i) => t + i.price * i.quantity, 0); 
    },
  },

  methods: {
    goToCheckout() {
      router.visit('/check');
    },

    clearRestaurantCart() {
      this.$emit(
        !this.currentRestaurantId ? 'clear-cart' : 'clear-restaurant-cart', 
        this.id
      );
    },

    async loadCartIcon() {
      try {
        const { data } = await api.get('/site_settings', {
          params: { id: 'eq.1', select: 'cart_empty_icon' },
        });
        if (data?.[0]?.cart_empty_icon) {
          this.cartEmptyIcon = data[0].cart_empty_icon;
        }
      } catch (err) {
        console.error('Ошибка загрузки иконки корзины:', err);
      }
    },

    onIconError(e) {
      e.target.src = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="170" viewBox="0 0 200 170" fill="none">' +
        '<rect x="40" y="20" width="120" height="110" rx="12" stroke="%23ddd" stroke-width="3" fill="none"/>' +
        '<line x1="40" y1="50" x2="160" y2="50" stroke="%23ddd" stroke-width="3"/>' +
        '<circle cx="70" cy="150" r="10" stroke="%23ddd" stroke-width="3" fill="none"/>' +
        '<circle cx="130" cy="150" r="10" stroke="%23ddd" stroke-width="3" fill="none"/>' +
        '<text x="100" y="90" text-anchor="middle" fill="%23ccc" font-size="14" font-family="sans-serif">Пусто</text>' +
        '</svg>'
      );
    },
  },

  mounted() {
    this.loadCartIcon();
  },
};
</script>

<style scoped>
.cart {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
  min-height: 600px;
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.cart h2 {
  margin: 0 0 20px;
  font-size: 20px;
  font-weight: 700;
  color: #1e1e1e;
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
  flex: 1;
  padding: 40px 0;
  gap: 24px;
}

.empty-cart p {
  font-size: 16px;
  color: #999;
  font-weight: 500;
}

.empty-cart-icon {
  width: 200px;
  height: 170px;
  opacity: 0.5;
  object-fit: contain;
}

.cart-with-items {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  flex: 1;
  justify-content: space-between;
}

.cart-items {
  width: 100%;
  max-height: 400px;
  overflow-y: auto;
  padding: 8px 0;
  margin-bottom: 20px;
}

.cart-item {
  padding: 12px 0;
  border-bottom: 1px solid #f5f5f5;
}

.cart-item:last-child {
  border-bottom: none;
}

.item-info h4 {
  margin: 0 0 4px;
  font-size: 15px;
  font-weight: 600;
  color: #1e1e1e;
}

.item-info p {
  margin: 0;
  font-size: 14px;
  color: #999;
  font-weight: 500;
}

.cart-summary {
  width: 100%;
  padding: 20px 0 0;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 12px;
  border-top: 2px solid #f0f0f0;
}

.cart-summary p {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #1e1e1e;
}

.cart-summary button {
  background: #ff6b00;
  color: #fff;
  border: none;
  border-radius: 40px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  padding: 12px 20px;
  transition: 0.2s;
  width: 100%;
}

.cart-summary button:hover {
  background: #e05e00;
  transform: translateY(-2px);
}

.clear-btn {
  background: #fff !important;
  color: #e74c3c !important;
  border: 2px solid #e74c3c !important;
}

.clear-btn:hover {
  background: #fff5f5 !important;
}
</style>