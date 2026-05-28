<template>
  <div>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>
    <HeaderComponent />
    <div class="dish-page container">
      <button class="back-btn" @click="goBack">← Назад к меню</button>

      <div v-if="loading" class="status-msg">Загрузка...</div>
      <div v-else-if="!dish" class="status-msg error">Блюдо не найдено</div>

      <template v-else>
        <div class="dish-card">
          <div class="dish-image">
            <img :src="dish.img || placeholder" :alt="dish.name" width="400" height="320" loading="lazy" decoding="async" @error="onImageError" />
          </div>
          <div class="dish-info">
            <h1>{{ dish.name }}</h1>
            <p v-if="dishRatingText" class="dish-rating">{{ dishRatingText }}</p>
            <p class="price">{{ dish.price }} ₽</p>
            <p v-if="dish.description" class="desc">{{ dish.description }}</p>

            <div v-if="hasNutrition" class="nutrition-block">
              <h3>Пищевая ценность</h3>
              <div class="nutrition-grid">
                <div v-if="dish.calories != null" class="nutrition-item">
                  <span class="nutrition-value">{{ dish.calories }}</span>
                  <span class="nutrition-label">ккал</span>
                </div>
                <div v-if="dish.proteins != null" class="nutrition-item">
                  <span class="nutrition-value">{{ dish.proteins }} г</span>
                  <span class="nutrition-label">белки</span>
                </div>
                <div v-if="dish.fats != null" class="nutrition-item">
                  <span class="nutrition-value">{{ dish.fats }} г</span>
                  <span class="nutrition-label">жиры</span>
                </div>
                <div v-if="dish.carbs != null" class="nutrition-item">
                  <span class="nutrition-value">{{ dish.carbs }} г</span>
                  <span class="nutrition-label">углеводы</span>
                </div>
              </div>
            </div>

            <div v-if="dish.composition" class="composition">
              <h3>Состав</h3>
              <p>{{ dish.composition }}</p>
            </div>

            <div class="actions">
              <button class="add-btn" @click="addToCart">В корзину</button>
            </div>
          </div>
        </div>

        <section class="reviews-section">
          <h2>Отзывы</h2>

          <div v-if="user" class="review-form">
            <h3>Оставить отзыв</h3>
            <div class="rating-input" :style="starMaskVars">
              <button
                v-for="n in 5"
                :key="n"
                type="button"
                :class="['star', 'rating-star-btn', { active: n <= newReview.rating }]"
                @click="newReview.rating = n"
              ></button>
            </div>
            <textarea v-model="newReview.comment" placeholder="Ваш отзыв..." rows="3"></textarea>
            <button class="submit-review" :disabled="submittingReview" @click="submitReview">
              {{ submittingReview ? 'Отправка...' : 'Отправить' }}
            </button>
          </div>
          <p v-else class="auth-hint">
            <a :href="`/auth?redirect=/dish/${id}`">Войдите</a>, чтобы оставить отзыв
          </p>

          <div v-if="!reviews.length" class="empty-reviews">Пока нет отзывов</div>
          <div v-else class="reviews-list">
            <ReviewCardUser
              v-for="r in reviews"
              :key="r.id"
              :review="r"
              :can-edit="canEditReview(r)"
              :editing="editingId === r.id"
              :edit-rating="editState.rating"
              :edit-comment="editState.comment"
              :redact-icon="redactIcon"
              :star-icon="starIcon"
              @edit="startEdit(r)"
              @save="saveEdit(r.id)"
              @cancel="cancelEdit"
              @delete="deleteReview(r)"
              @update:editRating="editState.rating = $event"
              @update:editComment="editState.comment = $event"
            />
          </div>
        </section>
      </template>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import HeaderComponent from '../../Components/HeaderComponent.vue';
import ReviewCardUser from '../Components/ReviewCardUser.vue';
import { router } from '@inertiajs/vue3';
import {
  getCurrentUser,
  getLaravelApi,
  loadProfile,
} from '../supabase.js';
import { getCart, saveCart, notifyCartChanged, syncCartWithAuth } from '../cart.js';
import { loadSiteSettings } from '../settingsCache.js';
import { usePageSeo } from '../usePageSeo.js';

export default {
  name: 'DishPage',
  components: { HeaderComponent, Head, ReviewCardUser },
  props: { id: { type: Number, required: true } },
  setup() {
    const seo = usePageSeo('Блюдо — Лопать Подано', 'Состав, пищевая ценность и отзывы о блюде. Закажите доставку через Лопать Подано.');
    return { seo };
  },
  data() {
    return {
      loading: true,
      dish: null,
      reviews: [],
      user: null,
      redactIcon: '',
      starIcon: '',
      submittingReview: false,
      newReview: { rating: 5, comment: '' },
      placeholder: '',
      editingId: null,
      editState: { rating: 5, comment: '' },
    };
  },
  computed: {
    hasNutrition() {
      if (!this.dish) return false;
      return ['calories', 'proteins', 'fats', 'carbs'].some(
        k => this.dish[k] != null && this.dish[k] !== ''
      );
    },
    dishRatingText() {
      const r = Number(this.dish?.rating);
      if (r > 0) {
        const count = this.dish?.reviews_count ?? this.reviews.length;
        return `${r.toFixed(1)}${count ? ` (${count} отзывов)` : ''}`;
      }
      if (this.reviews.length) {
        const avg = this.reviews.reduce((s, x) => s + (Number(x.rating) || 0), 0) / this.reviews.length;
        return `${avg.toFixed(1)} (${this.reviews.length} отзывов)`;
      }
      return '';
    },
    starMaskVars() {
      if (!this.starIcon) return {};
      return { '--star-mask': `url("${this.starIcon}")` };
    },
  },
  methods: {
    async loadDish() {
      const { data } = await window.axios.get(`/api/products/${this.id}`);
      this.dish = data || null;
    },
    async loadReviews() {
      const { data } = await window.axios.get(`/api/products/${this.id}/reviews`);
      this.reviews = Array.isArray(data) ? data : [];
    },
    async checkUser() {
      this.user = await getCurrentUser();
    },
    canEditReview(r) {
      return this.user && r.user_id && String(r.user_id) === String(this.user.id);
    },
    startEdit(review) {
      this.editingId = review.id;
      this.editState = { rating: review.rating, comment: review.comment || '' };
    },
    cancelEdit() {
      this.editingId = null;
    },
    async saveEdit(id) {
      try {
        const http = await getLaravelApi();
        await http.patch(`/api/profile/reviews/${id}`, {
          rating: this.editState.rating,
          comment: this.editState.comment,
        });
        this.cancelEdit();
        await this.loadReviews();
      } catch {
        alert('Не удалось сохранить отзыв');
      }
    },
    async deleteReview(review) {
      if (!confirm('Удалить отзыв?')) return;
      try {
        const http = await getLaravelApi();
        await http.delete(`/api/profile/reviews/${review.id}`);
        await this.loadReviews();
      } catch {
        alert('Не удалось удалить отзыв');
      }
    },
    async submitReview() {
      if (!this.user || !this.newReview.comment.trim()) {
        alert('Напишите текст отзыва');
        return;
      }
      this.submittingReview = true;
      try {
        const profile = await loadProfile(this.user.id);
        const http = await getLaravelApi();
        const { data } = await http.post('/api/reviews', {
          product_id: this.id,
          author_name: profile?.full_name || this.user.user_metadata?.full_name || 'Пользователь',
          rating: this.newReview.rating,
          comment: this.newReview.comment.trim(),
        });
        if (data?.review) {
          this.reviews = [data.review, ...this.reviews.filter(r => r.id !== data.review.id)];
        }
        if (data?.product_rating != null && this.dish) {
          this.dish.rating = data.product_rating;
          this.dish.reviews_count = this.reviews.length;
        }
        this.newReview.comment = '';
        this.newReview.rating = 5;
        await this.loadReviews();
      } catch (e) {
        alert('Не удалось отправить отзыв');
        console.error(e);
      } finally {
        this.submittingReview = false;
      }
    },
    addToCart() {
      const cart = getCart();
      const restaurantId = this.dish.restaurant_id;
      const existing = cart.find(i => i.id === this.dish.id);
      if (existing) existing.quantity++;
      else {
        cart.push({
          ...this.dish,
          quantity: 1,
          restaurantId,
        });
      }
      saveCart(cart);
      notifyCartChanged();
      alert('Добавлено в корзину');
    },
    goBack() {
      if (this.dish?.restaurant_id) router.visit(`/product/${this.dish.restaurant_id}`);
      else router.visit('/restaurans');
    },
    formatDate(d) {
      return new Date(d).toLocaleString('ru-RU');
    },
    onImageError(e) {
      e.target.src = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="%23f0f0f0"/></svg>'
      );
    },
  },
  async mounted() {
    try {
      const settings = await loadSiteSettings();
      this.redactIcon = settings?.redact_icon || '';
      this.starIcon = settings?.star_icon || '';
      await syncCartWithAuth();
      await Promise.all([this.loadDish(), this.loadReviews(), this.checkUser()]);
    } finally {
      this.loading = false;
    }
  },
};
</script>

<style scoped>
.container { max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.back-btn { margin-bottom: 1.5rem; padding: 10px 18px; background: #fff; border: 2px solid #eee; border-radius: 40px; cursor: pointer; font-weight: 600; }
.back-btn:hover { border-color: #ff6b00; color: #ff6b00; }
.status-msg { text-align: center; padding: 3rem; color: #888; }
.status-msg.error { color: #e74c3c; }
.dish-card { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; background: #fff; border-radius: 24px; padding: 2rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin-bottom: 2rem; }
.dish-image img { width: 100%; border-radius: 16px; object-fit: cover; max-height: 320px; }
.dish-info h1 { margin: 0 0 8px; font-size: 28px; }
.dish-rating { margin: 0 0 8px; font-size: 15px; font-weight: 600; color: #ff6b00; }
.price { font-size: 24px; font-weight: 800; color: #ff6b00; margin: 0 0 12px; }
.desc { color: #666; line-height: 1.5; margin-bottom: 1rem; }
.nutrition-block { margin: 1.25rem 0; }
.nutrition-block h3 { margin: 0 0 12px; font-size: 16px; font-weight: 700; }
.nutrition-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.nutrition-item { background: #fff8f0; border: 1px solid #ffe0c2; border-radius: 14px; padding: 12px 8px; text-align: center; }
.nutrition-value { display: block; font-size: 18px; font-weight: 700; color: #ff6b00; }
.nutrition-label { font-size: 12px; color: #888; text-transform: lowercase; }
.composition { margin-top: 1.25rem; }
.composition h3 { margin: 0 0 8px; font-size: 16px; }
.composition p { color: #555; line-height: 1.5; }
.add-btn { margin-top: 1.5rem; padding: 14px 32px; background: #ff6b00; color: #fff; border: none; border-radius: 40px; font-size: 16px; font-weight: 700; cursor: pointer; }
.add-btn:hover { background: #e05e00; }
.reviews-section { background: #fff; border-radius: 24px; padding: 2rem; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.reviews-section h2 { margin: 0 0 1.5rem; }
.review-form { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #f0f0f0; }
.rating-input { margin-bottom: 10px; }
.rating-input { display: inline-flex; gap: 6px; }
.star {
  background: #c3c3c3;
  border: none;
  width: 28px;
  height: 28px;
  cursor: pointer;
}
.rating-input[style*="--star-mask"] .star {
  -webkit-mask-image: var(--star-mask);
  mask-image: var(--star-mask);
  -webkit-mask-size: contain;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
}
.star.active { background: var(--brand-gradient); }
.review-form textarea { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 12px; margin-bottom: 10px; box-sizing: border-box; }
.submit-review { padding: 10px 24px; background: #ff6b00; color: #fff; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
.auth-hint { color: #888; margin-bottom: 1rem; }
.auth-hint a { color: #ff6b00; }
.review-item { padding: 1rem 0; border-bottom: 1px solid #f5f5f5; }
.review-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
.stars { color: #ff6b00; }
.empty-reviews { color: #999; text-align: center; padding: 1rem; }
@media (max-width: 768px) {
  .dish-card { grid-template-columns: 1fr; }
  .nutrition-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
