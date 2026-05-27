<template>
  <div>
    <Head>
      <title>{{ pageSeo.title }}</title>
      <meta name="description" :content="pageSeo.description" />
    </Head>
    <HeaderComponent />
    
    <div class="container">
      <div class="ad">
        <!-- Заголовок ресторана -->
        <header class="page_header">
          <div v-if="product">
            <h1 class="restaurant-page-title">{{ product.title }}</h1>
            <div class="header-meta-row">
              <p>
                <img v-if="settings.star_icon" :src="settings.star_icon" class="inline-icon" alt="" />
                <span v-else class="inline-icon">★</span>
                {{ displayRating }}
                <span class="meta-sep">·</span>
                <img v-if="settings.time_icon" :src="settings.time_icon" class="inline-icon" alt="" />
                <span v-else class="inline-icon">🕐</span>
                {{ formatDelivery(product) }}
                <span class="meta-sep">·</span>
                <img v-if="settings.delivery_icon" :src="settings.delivery_icon" class="inline-icon" alt="" />
                <span v-else class="inline-icon">🛵</span>
                {{ product.free_delivery_text || 'Бесплатная доставка от 800 ₽' }}
              </p>
              <button type="button" class="reviews-link" @click="goToRestaurantReviews">Отзывы ресторана</button>
            </div>
          </div>
          <div v-else-if="loading" class="status-message">Загрузка...</div>
          <div v-else class="status-message error">Ресторан не найден</div>
        </header>

        <!-- Категории + кнопка назад в одной строке -->
        <div class="categories-row">
          <button @click="goToRestaurants" class="back-btn">
            ← Назад
          </button>
          
          <div v-if="categories.length > 1" class="categories-bar">
            <button 
              v-for="cat in categories" 
              :key="cat" 
              @click="scrollToCategory(cat)"
              :class="['category-tab', { active: activeCategory === cat }]"
            >
              {{ cat }}
            </button>
          </div>
        </div>

        <!-- Меню -->
        <main class="products">
          <div v-if="loadingProducts" class="status-message">Загрузка меню...</div>

          <template v-else>
            <div v-for="(items, category) in groupedByCategory" :key="category" :ref="el => categoryRefs[category] = el">
              <h3 class="category-title">{{ category }}</h3>
              
              <div class="category-items">
                <div v-for="item in items" :key="item.id" class="product-card">
                  <div class="product-img-wrapper">
                    <img 
                      :src="item.img || settings.restaurant_placeholder" 
                      :alt="item.name" 
                      loading="lazy" 
                      @error="onProductImageError"
                    />
                  </div>
                  <div class="product-info">
                    <h4>
                      <a href="#" class="dish-link" @click.prevent="goToDish(item.id)">{{ item.name }}</a>
                    </h4>
                    <p v-if="productRating(item)" class="product-rating">★ {{ productRating(item) }}</p>
                    <p class="description">{{ item.description }}</p>
                    <p v-if="item.calories" class="calories-hint">{{ item.calories }} ккал</p>
                    <p v-if="item.proteins != null || item.fats != null || item.carbs != null" class="macros-hint">
                      Б {{ item.proteins ?? '—' }} · Ж {{ item.fats ?? '—' }} · У {{ item.carbs ?? '—' }}
                    </p>
                  </div>
                  <div class="product-price">
                    <div class="price-cont">
                      <span class="price">{{ item.price }} ₽</span>
                      <div class="quantity-control" v-if="getCartQuantity(item.id) > 0">
                        <button @click="decreaseCartQuantity(item)" class="quantity-btn">−</button>
                        <span class="quantity-display">{{ getCartQuantity(item.id) }}</span>
                        <button @click="addToCart(item)" class="quantity-btn">+</button>
                      </div>
                      <button v-else @click="addToCart(item)" class="add-to-cart-btn">+</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div v-if="!loadingProducts && restaurantProducts.length === 0" class="status-message">
            В этом ресторане пока нет блюд
          </div>
        </main>
      </div>

      <!-- Корзина -->
      <div class="cart-wrapper">
        <CartComponent
          :cart-items="cartItems"
          :current-restaurant-id="id"
          @increase-quantity="increaseQuantity"
          @decrease-quantity="decreaseQuantity"
          @remove-item="removeItem"
          @clear-cart="clearCart"
          @clear-restaurant-cart="clearRestaurantCart"
          @checkout="checkout"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import HeaderComponent from '../../Components/HeaderComponent.vue';
import CartComponent from '../../Components/CartComponent.vue';
import { router } from '@inertiajs/vue3';
import { usePageSeo } from '../usePageSeo.js';
import axios from 'axios';
import {
  getCart,
  saveCart,
  notifyCartChanged,
  syncCartWithAuth,
} from '../cart.js';
import { getCurrentUser } from '../supabase.js'; // checkout only

const api = axios.create({
  baseURL: 'https://cuibxmcjdkgjffmmzwgd.supabase.co/rest/v1',
  headers: {
    apikey: 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
    Authorization: 'Bearer sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh',
    'Content-Type': 'application/json',
  },
});

export default {
  name: 'ProductPage',
  components: { HeaderComponent, CartComponent, Head },
  props: ['id'],
  data() {
    return {
      loading: true,
      loadingProducts: true,
      error: null,
      product: null,
      restaurantProducts: [],
      cartItems: [],
      activeCategory: '',
      categories: [],
      categoryRefs: {},
      settings: {
        restaurant_placeholder: '',
        star_icon: '★',
        time_icon: '🕐',
        delivery_icon: '🛵',
      },
    };
  },

  computed: {
    pageSeo() {
      const name = this.product?.title || 'Меню ресторана';
      return usePageSeo(
        `${name} — Лопать Подано`,
        `Меню «${name}»: закажите блюда с доставкой на дом через сервис Лопать Подано.`
      );
    },
    totalPrice() {
      return this.cartItems.reduce((t, i) => t + i.price * i.quantity, 0);
    },
    
    groupedByCategory() {
      const groups = {};
      this.restaurantProducts.forEach(item => {
        const cat = item.category || 'Без категории';
        if (!groups[cat]) groups[cat] = [];
        groups[cat].push(item);
      });
      return groups;
    },
    displayRating() {
      const r = Number(this.product?.rating);
      return r > 0 ? r.toFixed(1) : 'Нет отзывов';
    },
  },

  methods: {
    async loadSettings() {
      try {
        const { loadSiteSettings, applySiteSettings } = await import('../settingsCache.js');
        applySiteSettings(this.settings, await loadSiteSettings());
      } catch (err) {
        console.error('Ошибка загрузки настроек:', err);
      }
    },

    formatDelivery(r) {
      if (!r) return '—';
      if (r.delivery_time != null && !Number.isNaN(Number(r.delivery_time))) {
        return `${r.delivery_time} мин`;
      }
      if (r.delivery_time) return String(r.delivery_time);
      return '30–45 мин';
    },

    async loadRestaurant() {
      this.loading = true;
      try {
        const { data } = await window.axios.get(`/api/restaurants/${this.id}`);
        this.product = data || null;
      } catch {
        this.error = 'Не удалось загрузить ресторан';
      } finally {
        this.loading = false;
      }
    },

    productRating(item) {
      const r = Number(item?.rating);
      if (r > 0) return r.toFixed(1);
      return '';
    },

    async loadProducts() {
      this.loadingProducts = true;
      try {
        const { data } = await window.axios.get(`/api/restaurants/${this.id}/products`);
        this.restaurantProducts = data || [];
        const cats = [...new Set((data || []).map(i => i.category).filter(Boolean))];
        this.categories = cats;
        if (cats.length > 0) this.activeCategory = cats[0];
      } catch {
        this.restaurantProducts = [];
      } finally {
        this.loadingProducts = false;
      }
    },
    scrollToCategory(cat) {
      this.activeCategory = cat;
      const el = this.categoryRefs[cat];
      if (el) {
        const offset = el.getBoundingClientRect().top + window.scrollY - 140;
        window.scrollTo({ top: offset, behavior: 'smooth' });
      }
    },

    goToRestaurants() {
      router.visit('/restaurans');
    },
    goToRestaurantReviews() {
      router.visit(`/restaurant/${this.id}/reviews`);
    },

    getCartQuantity(id) {
      return this.cartItems.find(i => i.id === id)?.quantity || 0;
    },

    addToCart(product) {
      const restaurantId = product.restaurant_id || product.restaurantId || this.id;
      const item = this.cartItems.find(i => i.id === product.id);
      if (item) item.quantity++;
      else this.cartItems.push({ ...product, quantity: 1, restaurantId });
      this.persistCart();
    },
    persistCart() {
      saveCart(this.cartItems);
      notifyCartChanged();
    },
    refreshCart() {
      this.cartItems = [...getCart()];
    },

    decreaseCartQuantity(product) {
      const item = this.cartItems.find(i => i.id === product.id);
      if (!item) return;
      if (item.quantity > 1) {
        item.quantity--;
        this.persistCart();
      } else {
        this.removeItem(item);
      }
    },

    increaseQuantity(item) {
      item.quantity++;
      this.persistCart();
    },
    decreaseQuantity(item) {
      item.quantity > 1 ? item.quantity-- : this.removeItem(item);
    },
    
    removeItem(item) {
      const idx = this.cartItems.findIndex(i => i.id === item.id);
      if (idx !== -1) this.cartItems.splice(idx, 1);
      this.persistCart();
    },

    clearCart() {
      this.cartItems = [];
      this.persistCart();
    },
    
    clearRestaurantCart(id) {
      if (confirm('Очистить корзину этого ресторана?')) {
        this.cartItems = this.cartItems.filter(
          i => (i.restaurantId || i.restaurant_id) !== id
        );
        this.persistCart();
      }
    },

    async checkout() {
      if (!this.cartItems.length) return alert('Корзина пуста!');
      const user = await getCurrentUser();
      if (!user) {
        router.visit('/auth?redirect=/check');
        return;
      }
      router.visit('/check');
    },

    goToDish(id) {
      router.visit(`/dish/${id}`);
    },

    onProductImageError(e) {
      e.target.src = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="150" viewBox="0 0 200 150" fill="none"><rect width="200" height="150" fill="%23f0f0f0" rx="12"/><text x="100" y="80" text-anchor="middle" fill="%23ccc" font-size="14" font-family="sans-serif">Нет фото</text></svg>'
      );
    },
  },

  async mounted() {
    await syncCartWithAuth();
    await Promise.all([this.loadSettings(), this.loadRestaurant(), this.loadProducts()]);
    this.refreshCart();
    this._onCartUpdate = () => this.refreshCart();
    window.addEventListener('cart-updated', this._onCartUpdate);
  },

  beforeUnmount() {
    window.removeEventListener('cart-updated', this._onCartUpdate);
  },
};
</script>

<style scoped>
.container {
  display: flex;
  gap: 20px;
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  align-items: flex-start;
}

.page_header {
  background: #fff;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  text-align: center;
  margin-bottom: 1.5rem;
}
.restaurant-page-title { margin: 0 0 12px; font-size: 2rem; font-weight: 700; color: #1e1e1e; }
.header-meta-row {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 12px;
}
.page_header p {
  display: inline-flex; align-items: center; gap: 8px;
  margin: 0;
  min-height: 40px;
  padding: 8px 20px; border: 2px solid #ff6b00; border-radius: 40px;
  font-size: 14px; font-weight: 500; color: #ff6b00; background: #fff8f0;
  box-sizing: border-box;
}
.meta-sep { opacity: 0.6; }
.reviews-link {
  margin: 0;
  border: none;
  background: #ff6b00;
  color: #fff;
  border-radius: 40px;
  min-height: 40px;
  padding: 8px 18px;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.2;
  cursor: pointer;
  box-sizing: border-box;
  white-space: nowrap;
}
.reviews-link:hover { background: #e05e00; }
.inline-icon { width: 16px; height: 16px; object-fit: contain; }

.ad { flex: 1; min-width: 0; }

.cart-wrapper {
  position: sticky; top: 92px; width: 320px; flex-shrink: 0;
  max-height: calc(100vh - 112px); overflow-y: auto;
  scrollbar-width: thin; scrollbar-color: #ddd transparent;
}

/* ========== СТРОКА КАТЕГОРИЙ + КНОПКА НАЗАД ========== */
.categories-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  position: sticky;
  top: 72px;
  z-index: 10;
  padding: 12px 0;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  background: #fff;
  border: 2px solid #eee;
  border-radius: 40px;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  flex-shrink: 0;
}
.back-btn:hover {
  border-color: #ff6b00;
  color: #ff6b00;
  background: #fff8f0;
}

.categories-bar {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.category-tab {
  padding: 10px 20px;
  background: #fff;
  border: 2px solid #eee;
  border-radius: 40px;
  font-size: 14px;
  font-weight: 600;
  color: #888;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.category-tab:hover { border-color: #ff6b00; color: #ff6b00; }
.category-tab.active { background: #ff6b00; color: #fff; border-color: #ff6b00; }

.category-title {
  font-size: 22px; font-weight: 700; color: #1e1e1e;
  margin: 28px 0 16px; padding-bottom: 8px; border-bottom: 2px solid #f0f0f0;
}
.category-items { display: grid; grid-template-columns: 1fr; gap: 12px; }

.product-card {
  background: #fff; border-radius: 16px; display: flex; align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; overflow: hidden;
}
.product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.product-img-wrapper { width: 110px; height: 110px; flex-shrink: 0; background: #f0f0f0; overflow: hidden; }
.product-img-wrapper img { width: 100%; height: 100%; object-fit: cover; }
.product-info { flex: 1; padding: 12px 14px; display: flex; flex-direction: column; justify-content: center; min-width: 0; }
.product-info h4 { margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #1e1e1e; }
.product-rating { margin: 0 0 4px; font-size: 13px; font-weight: 600; color: #ff6b00; }
.description { color: #888; font-size: 13px; margin: 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.dish-link { color: inherit; text-decoration: none; }
.dish-link:hover { color: #ff6b00; }
.calories-hint { font-size: 12px; color: #ff6b00; margin: 4px 0 0; font-weight: 600; }
.macros-hint { font-size: 11px; color: #999; margin: 2px 0 0; }
.product-price { padding: 12px 14px 12px 0; flex-shrink: 0; }
.price-cont { display: flex; align-items: center; gap: 8px; }
.price { font-size: 17px; font-weight: 700; color: #ff6b00; white-space: nowrap; }
.add-to-cart-btn { background: #ff6b00; color: #fff; border: none; border-radius: 50%; width: 34px; height: 34px; font-size: 20px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0; }
.add-to-cart-btn:hover { background: #e05e00; transform: scale(1.1); }
.quantity-control { display: flex; align-items: center; gap: 6px; }
.quantity-btn { background: #ff6b00; color: #fff; border: none; border-radius: 50%; width: 28px; height: 28px; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: .2s; }
.quantity-btn:hover { background: #e05e00; transform: scale(1.08); }
.quantity-display { font-size: 15px; font-weight: 700; color: #1e1e1e; min-width: 22px; text-align: center; }
.status-message { text-align: center; padding: 3rem; font-size: 18px; color: #666; }
.status-message.error { color: #e74c3c; }
.inline-icon { width: 16px; height: 16px; vertical-align: middle; display: inline; }

@media (max-width: 900px) {
  .container { flex-direction: column; padding: 1rem; }
  .cart-wrapper { position: static; width: 100%; max-height: none; order: 1; }
  .ad { order: 0; }
  .restaurant-page-title { font-size: 1.5rem; }
  .product-img-wrapper { width: 90px; height: 90px; }
  .categories-row { flex-wrap: wrap; }
}
</style>