<template>
  <div>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>
    <HeaderComponent />
    <div class="page container">
      <button class="back-btn" @click="goBack">← Назад к ресторану</button>
      <h1>Отзывы: {{ pageTitle }}</h1>

      <div class="reviews-tabs-wrap">
        <p class="reviews-tabs-hint">Выберите тип отзывов</p>
        <div class="reviews-tabs">
          <button
            type="button"
            :class="['reviews-tab', { active: activeTab === 'restaurant' }]"
            @click="setActiveTab('restaurant')"
          >
            О ресторане
          </button>
          <button
            type="button"
            :class="['reviews-tab', { active: activeTab === 'dishes' }]"
            @click="setActiveTab('dishes')"
          >
            О блюдах
          </button>
        </div>
      </div>

      <div class="toolbar">
        <label>Сортировка:</label>
        <select v-model="sortMode">
          <option value="new">Сначала новые</option>
          <option value="old">Сначала старые</option>
          <option value="good">С высокой оценкой</option>
          <option value="bad">С низкой оценкой</option>
        </select>
      </div>

      <div v-if="pageLoading" class="empty">Загрузка...</div>

      <section v-show="!pageLoading && activeTab === 'restaurant'" class="section">
        <h2>Оценка ресторана: {{ restaurantRatingText }}</h2>
        <div v-if="user" class="review-form">
          <h3>Оставить отзыв о ресторане</h3>
          <div class="stars-input" :style="starMaskVars">
            <button v-for="n in 5" :key="n" type="button" class="rating-star-btn" :class="{ active: n <= restaurantReview.rating }" @click="restaurantReview.rating = n"></button>
          </div>
          <textarea v-model="restaurantReview.comment" rows="3" placeholder="Ваше пожелание..."></textarea>
          <button @click="submitRestaurantReview" :disabled="sendingRestaurantReview">{{ sendingRestaurantReview ? 'Отправка...' : 'Отправить' }}</button>
        </div>
        <p v-else><a :href="`/auth?redirect=/restaurant/${id}/reviews`">Войдите</a>, чтобы оставить отзыв</p>

        <div v-if="!restaurantReviewsSorted.length" class="empty reviews-list-spaced">Пока нет отзывов о ресторане</div>
        <div v-else class="reviews reviews-list-spaced">
          <ReviewCardUser
            v-for="r in pagedRestaurantReviews"
            :key="`rr-${r.id}`"
            :review="r"
            :can-edit="canEditReview(r)"
            :editing="isEditing('restaurant', r.id)"
            :edit-rating="editState.rating"
            :edit-comment="editState.comment"
            :redact-icon="redactIcon"
            :star-icon="starIcon"
            @edit="startEdit('restaurant', r)"
            @save="saveEdit('restaurant', r.id)"
            @cancel="cancelEdit"
            @delete="deleteReview('restaurant', r)"
            @update:editRating="editState.rating = $event"
            @update:editComment="editState.comment = $event"
          />
        </div>
        <div class="pager" v-if="restaurantTotalPages > 1">
          <button :disabled="restaurantPage <= 1" @click="restaurantPage--">←</button>
          <span>Страница {{ restaurantPage }} / {{ restaurantTotalPages }}</span>
          <button :disabled="restaurantPage >= restaurantTotalPages" @click="restaurantPage++">→</button>
        </div>
      </section>

      <section v-show="!pageLoading && activeTab === 'dishes'" class="section">
        <h2>Отзывы о блюдах ресторана</h2>

        <div v-if="user" class="review-form dish-review-form">
          <h3>Оставить отзыв о блюде</h3>
          <label class="field-label">Блюдо</label>
          <select v-model.number="dishReview.product_id">
            <option :value="null" disabled>Выберите блюдо</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <div class="stars-input" :style="starMaskVars">
            <button v-for="n in 5" :key="`d-${n}`" type="button" class="rating-star-btn" :class="{ active: n <= dishReview.rating }" @click="dishReview.rating = n"></button>
          </div>
          <textarea v-model="dishReview.comment" rows="3" placeholder="Ваш отзыв о блюде..."></textarea>
          <button @click="submitDishReview" :disabled="sendingDishReview || !dishReview.product_id">
            {{ sendingDishReview ? 'Отправка...' : 'Отправить' }}
          </button>
        </div>
        <p v-else><a :href="`/auth?redirect=/restaurant/${id}/reviews`">Войдите</a>, чтобы оставить отзыв о блюде</p>

        <div v-if="!productGroups.length" class="empty reviews-list-spaced">Пока нет отзывов о блюдах</div>
        <div v-else class="reviews-list-spaced">
          <div v-for="group in pagedProductGroups" :key="group.product_id" class="product-group">
            <h3>{{ group.product_name }} <span v-if="group.product_rating" class="group-rating">{{ group.product_rating }}</span></h3>
            <ReviewCardUser
              v-for="r in group.reviews"
              :key="`pr-${r.id}`"
              :review="r"
              :can-edit="canEditReview(r)"
              :editing="isEditing('dish', r.id)"
              :edit-rating="editState.rating"
              :edit-comment="editState.comment"
              :redact-icon="redactIcon"
              :star-icon="starIcon"
              @edit="startEdit('dish', r)"
              @save="saveEdit('dish', r.id)"
              @cancel="cancelEdit"
              @delete="deleteReview('dish', r)"
              @update:editRating="editState.rating = $event"
              @update:editComment="editState.comment = $event"
            />
          </div>
        </div>
        <div class="pager" v-if="dishesTotalPages > 1">
          <button :disabled="dishesPage <= 1" @click="dishesPage--">←</button>
          <span>Страница {{ dishesPage }} / {{ dishesTotalPages }}</span>
          <button :disabled="dishesPage >= dishesTotalPages" @click="dishesPage++">→</button>
        </div>
      </section>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import HeaderComponent from '../../Components/HeaderComponent.vue';
import ReviewCardUser from '../Components/ReviewCardUser.vue';
import { router } from '@inertiajs/vue3';
import { getCurrentUser, loadProfile, getLaravelApi } from '../supabase.js';
import { loadSiteSettings } from '../settingsCache.js';
import { usePageSeo } from '../usePageSeo.js';

export default {
  name: 'RestaurantReviewsPage',
  components: { HeaderComponent, Head, ReviewCardUser },
  props: {
    id: { type: Number, required: true },
    initialRestaurantTitle: { type: String, default: null },
  },
  setup(props) {
    const title = props.initialRestaurantTitle
      ? `Отзывы: ${props.initialRestaurantTitle} — Лопать Подано`
      : 'Отзывы о ресторане — Лопать Подано';
    const seo = usePageSeo(title, 'Читайте и оставляйте отзывы о ресторане и блюдах Лопать Подано. Оценки помогают выбрать лучшее меню.');
    return { seo };
  },
  data() {
    return {
      pageLoading: true,
      restaurant: null,
      user: null,
      redactIcon: '',
      starIcon: '',
      activeTab: 'restaurant',
      sortMode: 'new',
      restaurantPage: 1,
      dishesPage: 1,
      pageSize: 8,
      restaurantReviews: [],
      productReviews: [],
      products: [],
      restaurantReview: { rating: 5, comment: '' },
      dishReview: { product_id: null, rating: 5, comment: '' },
      sendingRestaurantReview: false,
      sendingDishReview: false,
      editingType: null,
      editingId: null,
      editState: { rating: 5, comment: '' },
    };
  },
  computed: {
    pageTitle() {
      const t = this.restaurant?.title || this.restaurant?.name || this.initialRestaurantTitle;
      if (t) return t;
      return this.pageLoading ? 'Загрузка...' : `Ресторан #${this.id}`;
    },
    restaurantReviewsSorted() {
      return this.sortReviews([...this.restaurantReviews]);
    },
    restaurantRating() {
      if (!this.restaurantReviews.length) return null;
      const avg = this.restaurantReviews.reduce((a, b) => a + (Number(b.rating) || 0), 0) / this.restaurantReviews.length;
      return Number(avg.toFixed(1));
    },
    restaurantRatingText() {
      if (!this.restaurantRating) return 'пока нет оценок';
      return `${this.restaurantRating} (${this.restaurantReviews.length} отзывов)`;
    },
    pagedRestaurantReviews() {
      const start = (this.restaurantPage - 1) * this.pageSize;
      return this.restaurantReviewsSorted.slice(start, start + this.pageSize);
    },
    restaurantTotalPages() {
      return Math.max(1, Math.ceil(this.restaurantReviewsSorted.length / this.pageSize));
    },
    productGroups() {
      const map = {};
      for (const review of this.sortReviews([...this.productReviews])) {
        const product = this.products.find(p => p.id === review.product_id);
        const name = product?.name || `Блюдо #${review.product_id}`;
        if (!map[review.product_id]) {
          const ratings = this.productReviews
            .filter(r => r.product_id === review.product_id)
            .map(r => Number(r.rating) || 0);
          const avg = ratings.length
            ? Number((ratings.reduce((a, b) => a + b, 0) / ratings.length).toFixed(1))
            : 0;
          map[review.product_id] = {
            product_id: review.product_id,
            product_name: name,
            product_rating: avg > 0 ? avg : null,
            reviews: [],
          };
        }
        map[review.product_id].reviews.push(review);
      }
      return Object.values(map);
    },
    pagedProductGroups() {
      const start = (this.dishesPage - 1) * this.pageSize;
      return this.productGroups.slice(start, start + this.pageSize);
    },
    dishesTotalPages() {
      return Math.max(1, Math.ceil(this.productGroups.length / this.pageSize));
    },
    starMaskVars() {
      if (!this.starIcon) return {};
      return { '--star-mask': `url("${this.starIcon}")` };
    },
  },
  watch: {
    sortMode() {
      this.restaurantPage = 1;
      this.dishesPage = 1;
    },
  },
  methods: {
    canEditReview(r) {
      return this.user && r.user_id && String(r.user_id) === String(this.user.id);
    },
    isEditing(type, id) {
      return this.editingType === type && this.editingId === id;
    },
    startEdit(type, review) {
      this.editingType = type;
      this.editingId = review.id;
      this.editState = { rating: review.rating, comment: review.comment || '' };
    },
    cancelEdit() {
      this.editingType = null;
      this.editingId = null;
    },
    async saveEdit(type, id) {
      try {
        const http = await getLaravelApi();
        const payload = { rating: this.editState.rating, comment: this.editState.comment };
        if (type === 'restaurant') {
          await http.patch(`/api/profile/restaurant-reviews/${id}`, payload);
        } else {
          await http.patch(`/api/profile/reviews/${id}`, payload);
        }
        this.cancelEdit();
        await this.loadAll();
      } catch {
        alert('Не удалось сохранить отзыв');
      }
    },
    async deleteReview(type, review) {
      if (!confirm('Удалить отзыв?')) return;
      try {
        const http = await getLaravelApi();
        if (type === 'restaurant') {
          await http.delete(`/api/profile/restaurant-reviews/${review.id}`);
        } else {
          await http.delete(`/api/profile/reviews/${review.id}`);
        }
        await this.loadAll();
      } catch {
        alert('Не удалось удалить отзыв');
      }
    },
    setActiveTab(tab) {
      this.activeTab = tab;
      this.cancelEdit();
    },
    sortReviews(list) {
      if (this.sortMode === 'old') return list.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
      if (this.sortMode === 'good') return list.sort((a, b) => (b.rating || 0) - (a.rating || 0));
      if (this.sortMode === 'bad') return list.sort((a, b) => (a.rating || 0) - (b.rating || 0));
      return list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    },
    applyPageData(data) {
      this.restaurant = data.restaurant || null;
      this.restaurantReviews = data.restaurant_reviews || [];
      this.products = data.products || [];
      this.productReviews = data.product_reviews || [];
      if (this.products.length && !this.dishReview.product_id) {
        this.dishReview.product_id = this.products[0].id;
      }
    },
    async loadAll() {
      this.pageLoading = true;
      try {
        const { data } = await window.axios.get(`/api/restaurants/${this.id}/reviews-page`);
        this.applyPageData(data);
      } catch (e) {
        console.error(e);
      } finally {
        this.pageLoading = false;
      }
    },
    async submitRestaurantReview() {
      if (!this.user || !this.restaurantReview.comment.trim()) return;
      this.sendingRestaurantReview = true;
      try {
        const profile = await loadProfile(this.user.id);
        const http = await getLaravelApi();
        await http.post(`/api/restaurants/${this.id}/reviews`, {
          rating: this.restaurantReview.rating,
          comment: this.restaurantReview.comment.trim(),
          author_name: profile?.full_name || this.user.user_metadata?.full_name || 'Пользователь',
        });
        this.restaurantReview.comment = '';
        this.restaurantReview.rating = 5;
        await this.loadAll();
      } finally {
        this.sendingRestaurantReview = false;
      }
    },
    async submitDishReview() {
      if (!this.user || !this.dishReview.product_id || !this.dishReview.comment.trim()) return;
      this.sendingDishReview = true;
      try {
        const profile = await loadProfile(this.user.id);
        const http = await getLaravelApi();
        await http.post('/api/reviews', {
          product_id: this.dishReview.product_id,
          author_name: profile?.full_name || this.user.user_metadata?.full_name || 'Пользователь',
          rating: this.dishReview.rating,
          comment: this.dishReview.comment.trim(),
        });
        this.dishReview.comment = '';
        this.dishReview.rating = 5;
        await this.loadAll();
      } catch (e) {
        alert('Не удалось отправить отзыв');
        console.error(e);
      } finally {
        this.sendingDishReview = false;
      }
    },
    formatDate(d) { return new Date(d).toLocaleString('ru-RU'); },
    goBack() { router.visit(`/product/${this.id}`); },
  },
  async mounted() {
    try {
      const settings = await loadSiteSettings();
      this.redactIcon = settings?.redact_icon || '';
      this.starIcon = settings?.star_icon || '';
    } catch { /* ignore */ }
    this.user = await getCurrentUser();
    await this.loadAll();
  },
};
</script>

<style scoped>
.container { max-width: 980px; margin: 0 auto; padding: 2rem 1.5rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.back-btn { margin-bottom: 1rem; padding: 10px 18px; border: 2px solid #eee; border-radius: 40px; background: #fff; cursor: pointer; }
.back-btn:hover { border-color: #ff6b00; color: #ff6b00; }
h1 { margin: 0 0 1rem; }
.reviews-tabs-wrap { margin-bottom: 1.25rem; }
.reviews-tabs-hint { font-size: 14px; color: #888; margin: 0 0 12px; }
.reviews-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.reviews-tab {
  padding: 10px 18px;
  border: 2px solid #eee;
  border-radius: 40px;
  background: #fff;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
}
.reviews-tab:hover { border-color: #ff6b00; color: #ff6b00; }
.reviews-tab.active { background: #ff6b00; border-color: #ff6b00; color: #fff; }
.toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; }
.toolbar select { padding: 8px 10px; border: 1px solid #ddd; border-radius: 8px; }
.section { background: #fff; border-radius: 16px; padding: 1.2rem; margin-bottom: 1rem; }
.review-form textarea,
.review-form select { width: 100%; border: 2px solid #eee; border-radius: 10px; padding: 10px; box-sizing: border-box; margin: 8px 0; }
.review-form button { border: none; background: #ff6b00; color: #fff; padding: 10px 16px; border-radius: 10px; cursor: pointer; }
#1 { border: none; background: #ff6b00; color: #fff; padding: 10px 16px; border-radius: 10px; cursor: pointer; }
.field-label { font-size: 13px; color: #666; display: block; margin-top: 4px; }
.dish-review-form { margin-bottom: 0; padding-bottom: 1rem; border-bottom: 1px solid #f0f0f0; }
.reviews-list-spaced { margin-top: 1.75rem; }
.stars-input { display: inline-flex; gap: 6px; }
.stars-input button {
  border: none;
  width: 24px;
  height: 24px;
  background: #c3c3c3;
  cursor: pointer;
  padding: 0;
}
.stars-input[style*="--star-mask"] button {
  -webkit-mask-image: var(--star-mask);
  mask-image: var(--star-mask);
  -webkit-mask-size: contain;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
}
.stars-input button.active { background: var(--brand-gradient); }
.product-group { margin-bottom: 1.5rem; }
.product-group h3 { margin: 0 0 12px; }
.group-rating { font-size: 14px; color: #333; font-weight: 600; }
.empty { color: #999; text-align: center; padding: 1rem; }
.pager { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 12px; }
.pager button { padding: 8px 14px; border: 2px solid #eee; border-radius: 8px; background: #fff; cursor: pointer; }
.pager button:disabled { opacity: 0.4; cursor: not-allowed; }
a { color: #ff6b00; }
@media (max-width: 600px) {
  .container { padding: 1rem; }
  h1 { font-size: 1.35rem; }
}
</style>
