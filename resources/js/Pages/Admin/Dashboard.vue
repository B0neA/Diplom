<template>
  <div>
    <HeaderComponent />
    <div class="admin-page">
      <div class="container">
        <h1>Админ-панель</h1>

        <div v-if="loading" class="status-msg">Проверка доступа...</div>
        <div v-else-if="!allowed" class="status-msg error">
          Доступ запрещён. <a href="/">На главную</a>
        </div>

        <template v-else>
          <div class="tabs">
            <button :class="{ active: tab === 'orders' }" @click="switchTab('orders')">Заказы</button>
            <button :class="{ active: tab === 'products' }" @click="switchTab('products')">Товары</button>
            <button :class="{ active: tab === 'restaurants' }" @click="switchTab('restaurants')">Рестораны</button>
            <button :class="{ active: tab === 'feedback' }" @click="switchTab('feedback')">Обратная связь</button>
            <button :class="{ active: tab === 'support' }" @click="switchTab('support')">Поддержка</button>
            <button :class="{ active: tab === 'reviews' }" @click="switchTab('reviews')">Отзывы</button>
          </div>

          <!-- Заказы -->
          <section v-if="tab === 'orders'" class="panel">
            <div class="panel-toolbar">
              <button type="button" class="archive-open-btn" @click="openArchiveModal('order')">
                Архив заказов
                <span v-if="archivedOrders.length" class="archive-badge">{{ archivedOrders.length }}</span>
              </button>
            </div>
            <div v-if="ordersLoading" class="status-msg">Загрузка заказов...</div>
            <div v-else class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="o in orders" :key="o.id">
                    <td>#{{ o.id }}</td>
                    <td>{{ o.customer_name }}</td>
                    <td>{{ o.customer_phone }}</td>
                    <td>{{ o.total_amount }} ₽</td>
                    <td>
                      <select :value="o.status" @change="updateOrderStatus(o.id, $event.target.value)">
                        <option value="new">Новый</option>
                        <option value="processing">Готовится</option>
                        <option value="delivering">В пути</option>
                        <option value="delivered">Доставлен</option>
                        <option value="cancelled">Отменён</option>
                      </select>
                    </td>
                    <td class="actions-cell">
                      <button type="button" class="danger-btn" @click="deleteOrder(o.id)">Удалить</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Товары -->
          <section v-if="tab === 'products'" class="panel">
            <div class="panel-toolbar">
              <button class="add-btn add-btn--inline" @click="openProductForm()">+ Добавить товар</button>
              <button type="button" class="archive-open-btn" @click="openArchiveModal('product')">
                Архив
                <span v-if="archivedProducts.length" class="archive-badge">{{ archivedProducts.length }}</span>
              </button>
            </div>

            <div v-if="productFormOpen" class="product-form">
              <h3>{{ editingProduct ? 'Редактировать' : 'Новый товар' }}</h3>
              <div class="form-grid">
                <label>
                  <span class="field-label">Название</span>
                  <input v-model="productForm.name" placeholder="Название блюда" />
                </label>
                <label>
                  <span class="field-label">Цена, ₽</span>
                  <input
                    v-model.number="productForm.price"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="0"
                    @input="clampProductField('price')"
                  />
                </label>
                <label class="full-width">
                  <span class="field-label">Ресторан</span>
                  <select v-model.number="productForm.restaurant_id" class="form-select">
                    <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.title }}</option>
                  </select>
                </label>
                <label>
                  <span class="field-label">Категория</span>
                  <input v-model="productForm.category" placeholder="Например: Пицца" />
                </label>
                <label>
                  <span class="field-label">Калории</span>
                  <input v-model.number="productForm.calories" type="number" min="0" step="1" placeholder="ккал" @input="clampProductField('calories')" />
                </label>
                <label>
                  <span class="field-label">Белки (г)</span>
                  <input v-model.number="productForm.proteins" type="number" min="0" step="0.1" placeholder="0" @input="clampProductField('proteins')" />
                </label>
                <label>
                  <span class="field-label">Жиры (г)</span>
                  <input v-model.number="productForm.fats" type="number" min="0" step="0.1" placeholder="0" @input="clampProductField('fats')" />
                </label>
                <label>
                  <span class="field-label">Углеводы (г)</span>
                  <input v-model.number="productForm.carbs" type="number" min="0" step="0.1" placeholder="0" @input="clampProductField('carbs')" />
                </label>
                <label class="full-width">
                  <span class="field-label">URL изображения</span>
                  <input v-model="productForm.img" placeholder="https://..." />
                </label>
                <label class="full-width">
                  <span class="field-label">Описание</span>
                  <textarea v-model="productForm.description" placeholder="Краткое описание" rows="2"></textarea>
                </label>
                <label class="full-width">
                  <span class="field-label">Состав</span>
                  <textarea v-model="productForm.composition" placeholder="Ингредиенты" rows="2"></textarea>
                </label>
              </div>
              <div class="form-actions">
                <button @click="saveProduct" :disabled="savingProduct">{{ savingProduct ? 'Сохранение...' : 'Сохранить' }}</button>
                <button class="cancel" @click="productFormOpen = false">Отмена</button>
              </div>
            </div>

            <div v-if="productsLoading" class="status-msg">Загрузка товаров...</div>
            <div v-else class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Ресторан</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in products" :key="p.id">
                    <td>{{ p.id }}</td>
                    <td>{{ p.name }}</td>
                    <td>{{ p.price }} ₽</td>
                    <td>{{ restaurantName(p.restaurant_id) }}</td>
                    <td class="actions-cell">
                      <button class="edit-link" @click="openProductForm(p)">Изменить</button>
                      <button class="danger-btn" @click="deleteProduct(p.id)">Удалить</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Рестораны -->
          <section v-if="tab === 'restaurants'" class="panel">
            <div class="panel-toolbar">
              <button class="add-btn add-btn--inline" @click="openRestaurantForm()">+ Добавить ресторан</button>
              <button type="button" class="archive-open-btn" @click="openArchiveModal('restaurant')">
                Архив
                <span v-if="archivedRestaurants.length" class="archive-badge">{{ archivedRestaurants.length }}</span>
              </button>
            </div>

            <div v-if="restaurantFormOpen" class="product-form">
              <h3>{{ editingRestaurant ? 'Редактировать ресторан' : 'Новый ресторан' }}</h3>
              <div class="form-grid">
                <label class="full-width">
                  <span class="field-label">Название</span>
                  <input v-model="restaurantForm.title" placeholder="Название ресторана" />
                </label>
                <label>
                  <span class="field-label">Рейтинг</span>
                  <input v-model.number="restaurantForm.rating" type="number" min="0" max="5" step="0.1" placeholder="0–5" />
                </label>
                <label>
                  <span class="field-label">Время доставки (мин)</span>
                  <input v-model.number="restaurantForm.delivery_time" type="number" min="0" placeholder="35" />
                </label>
                <label class="full-width">
                  <span class="field-label">URL изображения</span>
                  <input v-model="restaurantForm.img" placeholder="https://..." />
                </label>
                <label class="full-width">
                  <span class="field-label">Описание</span>
                  <textarea v-model="restaurantForm.description" placeholder="О ресторане" rows="2"></textarea>
                </label>
              </div>
              <div class="form-actions">
                <button @click="saveRestaurant" :disabled="savingRestaurant">{{ savingRestaurant ? 'Сохранение...' : 'Сохранить' }}</button>
                <button class="cancel" @click="restaurantFormOpen = false">Отмена</button>
              </div>
            </div>

            <div v-if="restaurantsLoading" class="status-msg">Загрузка ресторанов...</div>
            <div v-else class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Рейтинг</th>
                    <th>Доставка</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in restaurants" :key="r.id">
                    <td>{{ r.id }}</td>
                    <td>{{ r.title }}</td>
                    <td>{{ r.rating }}</td>
                    <td>{{ r.delivery_time ? r.delivery_time + ' мин' : '—' }}</td>
                    <td class="actions-cell">
                      <button class="edit-link" @click="openRestaurantForm(r)">Изменить</button>
                      <button class="danger-btn" @click="deleteRestaurant(r.id)">Удалить</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Заявки в поддержку -->
          <section v-if="tab === 'support'" class="panel">
            <div v-if="supportLoading" class="status-msg">Загрузка...</div>
            <div v-else-if="!supportList.length" class="status-msg">Заявок нет</div>
            <div v-else class="feedback-list">
              <div v-for="req in supportList" :key="req.id" class="feedback-card">
                <div class="feedback-header">
                  <strong>{{ req.name }}</strong>
                  <span class="feedback-phone">{{ formatPhone(req.phone) }}</span>
                  <button class="danger-btn" @click="deleteSupport(req.id)">Удалить</button>
                </div>
                <p class="support-message">{{ req.message }}</p>
                <small>{{ formatDate(req.created_at) }}</small>
              </div>
            </div>
          </section>

          <!-- Обратная связь -->
          <section v-if="tab === 'feedback'" class="panel">
            <div v-if="feedbackLoading" class="status-msg">Загрузка...</div>
            <div v-else-if="!feedbackList.length" class="status-msg">Сообщений нет</div>
            <div v-else class="feedback-list">
              <div v-for="msg in feedbackList" :key="msg.id" class="feedback-card">
                <div class="feedback-header">
                  <strong>{{ msg.name }}</strong>
                  <span class="feedback-email">{{ msg.email }}</span>
                  <button class="danger-btn" @click="deleteFeedback(msg.id)">Удалить</button>
                </div>
                <p>{{ msg.message }}</p>
                <small>{{ formatDate(msg.created_at) }}</small>
              </div>
            </div>
          </section>

          <!-- Отзывы -->
          <section v-if="tab === 'reviews'" class="panel">
            <div class="reviews-toolbar">
              <label>Сортировка:</label>
              <select v-model="reviewsSort" @change="reloadReviews">
                <option value="new">Сначала новые</option>
                <option value="old">Сначала старые</option>
                <option value="good">От хороших к плохим</option>
                <option value="bad">От плохих к хорошим</option>
              </select>
              <label>Тип:</label>
              <select v-model="reviewsTypeFilter" @change="onReviewsFilterChange">
                <option value="all">Все</option>
                <option value="restaurant">Ресторан</option>
                <option value="dish">Блюдо</option>
              </select>
            </div>

            <div v-if="reviewRestaurantTabs.length" class="checkout-tabs-wrap">
              <div class="checkout-tabs">
                <button
                  v-if="reviewRestaurantTabs.length > 1"
                  type="button"
                  :class="['checkout-tab', { active: reviewsRestaurantTab === 'all' }]"
                  @click="setReviewsRestaurantTab('all')"
                >
                  Все рестораны
                </button>
                <button
                  v-for="r in reviewRestaurantTabs"
                  :key="r.id"
                  type="button"
                  :class="['checkout-tab', { active: String(reviewsRestaurantTab) === String(r.id) }]"
                  @click="setReviewsRestaurantTab(r.id)"
                >
                  {{ r.title }}
                </button>
              </div>
            </div>

            <div v-if="reviewsLoading" class="status-msg">Загрузка отзывов...</div>
            <div v-else-if="!filteredReviewGroups.length" class="status-msg">Отзывов нет</div>
            <div v-else class="reviews-grouped">
              <div v-for="group in pagedReviewGroups" :key="group.restaurant_id" class="restaurant-group">
                <h3 class="group-title">🍽 {{ group.restaurant_title }}</h3>
                <div v-for="dish in group.dishes" :key="dish.product_id ?? 'restaurant'" class="dish-group">
                  <h4>
                    {{ dish.product_name }}
                    <span v-if="dish.review_type === 'dish'" class="type-badge">Блюдо</span>
                    <span v-else class="type-badge type-badge--restaurant">Ресторан</span>
                  </h4>
                  <div v-for="rev in dish.reviews" :key="rev.id" class="review-admin-card">
                    <template v-if="editingReviewId === rev.id">
                      <p class="review-edit-author">Автор: <strong>{{ reviewEdit.author_name }}</strong></p>
                      <label class="review-edit-field">
                        <span class="field-label">Оценка</span>
                        <select v-model.number="reviewEdit.rating">
                        <option v-for="n in 5" :key="n" :value="n">{{ n }} ★</option>
                        </select>
                      </label>
                      <label class="review-edit-field">
                        <span class="field-label">Комментарий</span>
                        <textarea v-model="reviewEdit.comment" rows="2" placeholder="Текст отзыва"></textarea>
                      </label>
                      <div class="mini-actions">
                        <button @click="saveReviewEdit(rev.id)">Сохранить</button>
                        <button class="cancel" @click="editingReviewId = null">Отмена</button>
                      </div>
                    </template>
                    <template v-else>
                      <div class="review-admin-header">
                        <strong>{{ rev.author_name }}</strong>
                        <span class="stars">{{ '★'.repeat(rev.rating) }}</span>
                        <div class="review-btns">
                          <button class="edit-link" @click="startEditReview(rev)">Изменить</button>
                          <button class="danger-btn" @click="deleteReview(rev)">Удалить</button>
                        </div>
                      </div>
                      <p>{{ rev.comment }}</p>
                      <small>{{ formatDate(rev.created_at) }}</small>
                    </template>
                  </div>
                </div>
              </div>
              <div class="pager" v-if="reviewsTotalPages > 1">
                <button :disabled="reviewsPage <= 1" @click="reviewsPage--">←</button>
                <span>Страница {{ reviewsPage }} / {{ reviewsTotalPages }}</span>
                <button :disabled="reviewsPage >= reviewsTotalPages" @click="reviewsPage++">→</button>
              </div>
            </div>
          </section>
        </template>
      </div>
    </div>

    <AdminMiniModal
      :show="confirmModal.show"
      mode="confirm"
      :title="confirmModal.title"
      :message="confirmModal.message"
      confirm-label="Удалить"
      @confirm="onConfirmDelete"
      @cancel="closeConfirmModal"
    />
    <AdminMiniModal
      :show="restoreNotice.show"
      mode="notice"
      :title="restoreNotice.title"
      :message="restoreNotice.message"
      @close="restoreNotice.show = false"
    />
    <AdminArchiveModal
      :show="archiveModal.show"
      :title="archiveModalTitle"
      :hint="archiveModalHint"
      :items="archiveModalItems"
      :loading="archiveLoading"
      :empty-text="archiveModalEmptyText"
      :show-products-count="archiveModal.type === 'restaurant'"
      @close="closeArchiveModal"
      @restore="onArchiveRestore"
    />
  </div>
</template>

<script>
import HeaderComponent from '../../../Components/HeaderComponent.vue';
import AdminMiniModal from '../../../Components/AdminMiniModal.vue';
import AdminArchiveModal from '../../../Components/AdminArchiveModal.vue';
import { getCurrentUser, getCurrentSession, isAdmin, getLaravelApi } from '../../supabase.js';

const emptyProductForm = () => ({
  name: '', description: '', price: null, category: '', restaurant_id: null,
  img: '', composition: '', calories: null, proteins: null, fats: null, carbs: null,
});

const emptyRestaurantForm = () => ({
  title: '', rating: 4, img: '', delivery_time: 35, description: '',
});

const ARCHIVE_STORAGE_KEY = 'lopat_podano_admin_archive';

export default {
  name: 'AdminDashboard',
  components: { HeaderComponent, AdminMiniModal, AdminArchiveModal },
  data() {
    return {
      loading: true,
      allowed: false,
      tab: 'orders',
      orders: [],
      products: [],
      restaurants: [],
      feedbackList: [],
      supportList: [],
      supportLoading: false,
      reviewsGrouped: [],
      ordersLoading: false,
      productsLoading: false,
      restaurantsLoading: false,
      restaurantFormOpen: false,
      editingRestaurant: null,
      savingRestaurant: false,
      restaurantForm: emptyRestaurantForm(),
      feedbackLoading: false,
      reviewsLoading: false,
      productFormOpen: false,
      editingProduct: null,
      savingProduct: false,
      productForm: emptyProductForm(),
      editingReviewId: null,
      reviewEdit: { author_name: '', rating: 5, comment: '' },
      reviewsSort: 'new',
      reviewsPage: 1,
      reviewsPageSize: 1,
      reviewsRestaurantTab: 'all',
      reviewsTypeFilter: 'all',
      archiveItems: [],
      archiveLoading: false,
      archiveModal: { show: false, type: 'restaurant' },
      confirmModal: { show: false, title: '', message: '', action: null },
      restoreNotice: { show: false, title: '', message: '' },
    };
  },
  computed: {
    reviewRestaurantTabs() {
      const map = new Map();
      for (const g of this.reviewsGrouped) {
        const id = g.restaurant_id;
        if (id === null || id === undefined) continue;
        const title = this.restaurantDisplayTitle(id, g.restaurant_title);
        map.set(String(id), { id, title });
      }
      return [...map.values()].sort((a, b) => String(a.title).localeCompare(String(b.title), 'ru'));
    },
    filteredReviewGroups() {
      let groups = this.reviewsGrouped;
      if (this.reviewsRestaurantTab !== 'all') {
        groups = groups.filter(g => String(g.restaurant_id) === String(this.reviewsRestaurantTab));
      }
      return groups.map(g => ({
        ...g,
        restaurant_title: this.restaurantDisplayTitle(g.restaurant_id, g.restaurant_title),
        dishes: (g.dishes || [])
          .map(dish => ({
            ...dish,
            reviews: (dish.reviews || []).filter(rev => this.reviewMatchesType(rev, dish)),
          }))
          .filter(dish => dish.reviews.length > 0),
      })).filter(g => g.dishes.length > 0);
    },
    reviewsTotalPages() {
      return Math.max(1, Math.ceil(this.filteredReviewGroups.length / this.reviewsPageSize));
    },
    pagedReviewGroups() {
      const start = (this.reviewsPage - 1) * this.reviewsPageSize;
      return this.filteredReviewGroups.slice(start, start + this.reviewsPageSize);
    },
    archivedProducts() {
      return this.archiveItems.filter(i => i.type === 'product');
    },
    archivedRestaurants() {
      return this.archiveItems.filter(i => i.type === 'restaurant');
    },
    archivedOrders() {
      return this.archiveItems.filter(i => i.type === 'order');
    },
    archiveModalItems() {
      if (this.archiveModal.type === 'restaurant') return this.archivedRestaurants;
      if (this.archiveModal.type === 'product') return this.archivedProducts;
      if (this.archiveModal.type === 'order') return this.archivedOrders;
      return [];
    },
    archiveModalTitle() {
      const titles = {
        restaurant: 'Архив ресторанов',
        product: 'Архив товаров',
        order: 'Архив заказов',
      };
      return titles[this.archiveModal.type] || 'Архив';
    },
    archiveModalHint() {
      if (this.archiveModal.type === 'restaurant') {
        return 'Восстановление вернёт ресторан и связанные блюда.';
      }
      if (this.archiveModal.type === 'order') {
        return 'Удалённые заказы можно вернуть в список.';
      }
      return 'Удалённые блюда можно вернуть в меню.';
    },
    archiveModalEmptyText() {
      const texts = {
        restaurant: 'Архив ресторанов пуст',
        product: 'Архив товаров пуст',
        order: 'Архив заказов пуст',
      };
      return texts[this.archiveModal.type] || 'Архив пуст';
    },
  },
  methods: {
    switchTab(name) {
      this.tab = name;
      if (name === 'orders' && !this.orders.length) this.loadOrders();
      if (name === 'products') {
        if (!this.restaurants.length) this.loadRestaurants();
        if (!this.products.length) this.loadProducts();
        this.loadArchive();
      }
      if (name === 'restaurants') {
        if (!this.restaurants.length) this.loadRestaurants();
        this.loadArchive();
      }
      if (name === 'feedback' && !this.feedbackList.length) this.loadFeedback();
      if (name === 'support' && !this.supportList.length) this.loadSupport();
      if (name === 'reviews') {
        if (!this.restaurants.length) this.loadRestaurants();
        if (!this.reviewsGrouped.length) this.loadReviewsGrouped();
      }
    },
    reviewMatchesType(rev, dish) {
      if (this.reviewsTypeFilter === 'all') return true;
      if (this.reviewsTypeFilter === 'restaurant') {
        return rev.source === 'restaurant' || dish.review_type === 'restaurant';
      }
      return rev.source === 'dish' || dish.review_type === 'dish';
    },
    setReviewsRestaurantTab(tab) {
      this.reviewsRestaurantTab = tab;
      this.reviewsPage = 1;
    },
    onReviewsFilterChange() {
      this.reviewsPage = 1;
    },
    syncReviewsRestaurantTab() {
      const tabs = this.reviewRestaurantTabs;
      if (!tabs.length) {
        this.reviewsRestaurantTab = 'all';
        return;
      }
      if (tabs.length === 1) {
        this.reviewsRestaurantTab = tabs[0].id;
        return;
      }
      if (
        this.reviewsRestaurantTab !== 'all' &&
        !tabs.find(r => String(r.id) === String(this.reviewsRestaurantTab))
      ) {
        this.reviewsRestaurantTab = 'all';
      }
    },
    isPlaceholderRestaurantTitle(title) {
      return /^Ресторан #\d+$/.test(String(title || '').trim());
    },
    restaurantName(id) {
      const r = this.restaurants.find(x => String(x.id) === String(id));
      const title = (r?.title || r?.name || '').trim();
      if (title) return title;
      const fromGroup = this.reviewsGrouped.find(g => String(g.restaurant_id) === String(id));
      const gTitle = (fromGroup?.restaurant_title || '').trim();
      if (gTitle && !this.isPlaceholderRestaurantTitle(gTitle)) return gTitle;
      return Number(id) > 0 ? `Ресторан #${id}` : 'Без ресторана';
    },
    restaurantDisplayTitle(id, titleFromApi) {
      const fromApi = String(titleFromApi || '').trim();
      if (fromApi && !this.isPlaceholderRestaurantTitle(fromApi)) return fromApi;
      const fromList = this.restaurantName(id);
      if (fromList && !this.isPlaceholderRestaurantTitle(fromList)) return fromList;
      return fromApi || fromList || 'Без ресторана';
    },
    formatDate(d) {
      return d ? new Date(d).toLocaleString('ru-RU') : '';
    },
    async checkAccess() {
      const session = await getCurrentSession();
      const user = session?.user ?? (await getCurrentUser());
      if (!user) {
        window.location.href = '/auth?redirect=/admin';
        return;
      }
      this.allowed = await isAdmin(user.id);
    },
    async loadRestaurants() {
      this.restaurantsLoading = true;
      try {
        const http = await getLaravelApi();
        const { data } = await http.get('/api/admin/restaurants');
        this.restaurants = data || [];
        if (this.restaurants.length && !this.productForm.restaurant_id) {
          this.productForm.restaurant_id = this.restaurants[0].id;
        }
      } catch (e) {
        console.error(e);
        alert('Не удалось загрузить рестораны');
      } finally {
        this.restaurantsLoading = false;
      }
    },
    async loadOrders() {
      this.ordersLoading = true;
      try {
        const http = await getLaravelApi();
        const { data } = await http.get('/api/admin/orders');
        this.orders = data || [];
      } catch {
        alert('Не удалось загрузить заказы');
      } finally {
        this.ordersLoading = false;
      }
    },
    async loadProducts() {
      this.productsLoading = true;
      try {
        const http = await getLaravelApi();
        const { data } = await http.get('/api/admin/products');
        this.products = data || [];
      } catch {
        alert('Не удалось загрузить товары');
      } finally {
        this.productsLoading = false;
      }
    },
    async loadFeedback() {
      this.feedbackLoading = true;
      try {
        const http = await getLaravelApi();
        const { data } = await http.get('/api/admin/feedback');
        this.feedbackList = data || [];
      } catch {
        alert('Не удалось загрузить обратную связь');
      } finally {
        this.feedbackLoading = false;
      }
    },
    async loadSupport() {
      this.supportLoading = true;
      try {
        const http = await getLaravelApi();
        const { data } = await http.get('/api/admin/support');
        this.supportList = Array.isArray(data) ? data : [];
      } catch {
        alert('Не удалось загрузить заявки');
        this.supportList = [];
      } finally {
        this.supportLoading = false;
      }
    },
    formatPhone(phone) {
      const d = String(phone || '').replace(/\D/g, '');
      if (d.length === 11 && d.startsWith('7')) {
        return `+7 (${d.slice(1, 4)}) ${d.slice(4, 7)}-${d.slice(7, 9)}-${d.slice(9, 11)}`;
      }
      return phone || '—';
    },
    async deleteSupport(id) {
      if (!confirm('Удалить заявку?')) return;
      try {
        const http = await getLaravelApi();
        await http.delete(`/api/admin/support/${id}`);
        this.supportList = this.supportList.filter(r => r.id !== id);
      } catch (e) {
        alert('Ошибка удаления');
        console.error(e);
      }
    },
    async loadReviewsGrouped() {
      this.reviewsLoading = true;
      try {
        const http = await getLaravelApi();
        const { data } = await http.get('/api/admin/reviews', { params: { sort: this.reviewsSort } });
        this.reviewsGrouped = data || [];
        this.syncReviewsRestaurantTab();
        if (this.reviewsPage > this.reviewsTotalPages) this.reviewsPage = 1;
      } catch (e) {
        console.error(e);
        alert(e?.response?.data?.error || 'Не удалось загрузить отзывы');
      } finally {
        this.reviewsLoading = false;
      }
    },
    async reloadReviews() {
      this.reviewsPage = 1;
      await this.loadReviewsGrouped();
    },
    clampProductField(key) {
      const v = this.productForm[key];
      if (v !== null && v !== '' && v < 0) {
        this.productForm[key] = 0;
      }
    },
    async updateOrderStatus(id, status) {
      try {
        const http = await getLaravelApi();
        const res = await http.patch(`/api/admin/orders/${id}`, { status });
        if (res.status >= 400) throw new Error('patch failed');
        const o = this.orders.find(x => x.id === id);
        if (o) o.status = status;
      } catch (e) {
        console.error(e);
        alert('Ошибка обновления статуса');
        await this.loadOrders();
      }
    },
    deleteOrder(id) {
      this.openConfirmModal({
        title: 'Удалить заказ?',
        message: 'Заказ будет перемещён в архив. Его можно восстановить во вкладке «Заказы».',
        action: () => this.performDeleteOrder(id),
      });
    },
    async performDeleteOrder(id) {
      try {
        const http = await getLaravelApi();
        const res = await http.delete(`/api/admin/orders/${id}`);
        if (res.status >= 400 || res.data?.error) throw new Error(res.data?.error || 'delete failed');
        this.orders = this.orders.filter(o => String(o.id) !== String(id));
        this.applyDeleteResponse(res.data);
      } catch (e) {
        console.error(e);
        alert(e?.response?.data?.error || 'Ошибка удаления заказа');
      }
    },
    openProductForm(product = null) {
      if (!this.restaurants.length) this.loadRestaurants();
      this.editingProduct = product;
      this.productForm = product
        ? { ...emptyProductForm(), ...product }
        : { ...emptyProductForm(), restaurant_id: this.restaurants[0]?.id || null };
      this.productFormOpen = true;
    },
    async saveProduct() {
      if (!this.productForm.restaurant_id) {
        alert('Выберите ресторан');
        return;
      }
      ['price', 'calories', 'proteins', 'fats', 'carbs'].forEach(k => this.clampProductField(k));
      if (this.productForm.price == null || this.productForm.price === '') {
        alert('Укажите цену');
        return;
      }
      this.savingProduct = true;
      try {
        const http = await getLaravelApi();
        if (this.editingProduct) {
          await http.patch(`/api/admin/products/${this.editingProduct.id}`, this.productForm);
        } else {
          await http.post('/api/admin/products', this.productForm);
        }
        this.productFormOpen = false;
        await this.loadProducts();
      } catch {
        alert('Ошибка сохранения товара');
      } finally {
        this.savingProduct = false;
      }
    },
    openRestaurantForm(restaurant = null) {
      this.editingRestaurant = restaurant;
      this.restaurantForm = restaurant
        ? { ...emptyRestaurantForm(), ...restaurant }
        : emptyRestaurantForm();
      this.restaurantFormOpen = true;
    },
    async saveRestaurant() {
      if (!this.restaurantForm.title?.trim()) {
        alert('Укажите название ресторана');
        return;
      }
      this.savingRestaurant = true;
      try {
        const http = await getLaravelApi();
        if (this.editingRestaurant) {
          await http.patch(`/api/admin/restaurants/${this.editingRestaurant.id}`, this.restaurantForm);
        } else {
          await http.post('/api/admin/restaurants', this.restaurantForm);
        }
        this.restaurantFormOpen = false;
        await this.loadRestaurants();
      } catch {
        alert('Ошибка сохранения ресторана');
      } finally {
        this.savingRestaurant = false;
      }
    },
    archiveItemKey(item) {
      return `${item.type}:${item.id}`;
    },
    readArchiveFromStorage() {
      try {
        const raw = sessionStorage.getItem(ARCHIVE_STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    },
    persistArchiveToStorage() {
      try {
        sessionStorage.setItem(ARCHIVE_STORAGE_KEY, JSON.stringify(this.archiveItems));
      } catch {
        /* ignore quota errors */
      }
    },
    mergeArchiveLists(...lists) {
      const map = new Map();
      for (const list of lists) {
        for (const item of list || []) {
          if (!item?.type || item.id === undefined || item.id === null) continue;
          map.set(this.archiveItemKey(item), item);
        }
      }
      return [...map.values()].sort(
        (a, b) => new Date(b.deleted_at || 0) - new Date(a.deleted_at || 0)
      );
    },
    async loadArchive() {
      const stored = this.readArchiveFromStorage();
      const previous = [...this.archiveItems];
      this.archiveLoading = true;
      try {
        const http = await getLaravelApi();
        const { data } = await http.get('/api/admin/archive');
        const server = Array.isArray(data) ? data : [];
        this.archiveItems = this.mergeArchiveLists(server, previous, stored);
        this.persistArchiveToStorage();
      } catch (e) {
        console.error('Не удалось загрузить архив', e);
        this.archiveItems = this.mergeArchiveLists(previous, stored);
        this.persistArchiveToStorage();
      } finally {
        this.archiveLoading = false;
      }
    },
    formatArchiveDate(iso) {
      return iso ? new Date(iso).toLocaleString('ru-RU') : '—';
    },
    async openArchiveModal(type) {
      this.archiveModal = { show: true, type };
      await this.loadArchive();
    },
    closeArchiveModal() {
      this.archiveModal = { ...this.archiveModal, show: false };
    },
    async onArchiveRestore(item) {
      await this.restoreItem(item);
    },
    upsertArchiveItem(item) {
      if (!item?.type || !item?.id) return;
      this.archiveItems = this.mergeArchiveLists([item], this.archiveItems);
      this.persistArchiveToStorage();
    },
    applyDeleteResponse(data) {
      if (!data?.entity) return;
      this.upsertArchiveItem({
        ...data.entity,
        deleted_at: data.deleted_at || data.entity.deleted_at,
      });
    },
    openConfirmModal({ title, message, action }) {
      this.confirmModal = { show: true, title, message, action };
    },
    closeConfirmModal() {
      this.confirmModal = { show: false, title: '', message: '', action: null };
    },
    onConfirmDelete() {
      const action = this.confirmModal.action;
      this.closeConfirmModal();
      if (typeof action === 'function') action();
    },
    showRestoreNotice(title, message) {
      this.restoreNotice = { show: true, title, message };
    },
    async restoreItem(item) {
      try {
        const http = await getLaravelApi();
        const paths = {
          restaurant: `/api/admin/restaurants/${item.id}/restore`,
          product: `/api/admin/products/${item.id}/restore`,
          order: `/api/admin/orders/${item.id}/restore`,
        };
        const path = paths[item.type];
        const { data } = await http.post(path);
        if (data?.error) throw new Error(data.error);

        if (item.type === 'restaurant') {
          const restaurantId = String(item.id);
          this.archiveItems = this.archiveItems.filter(i => {
            if (i.type === 'restaurant' && String(i.id) === restaurantId) return false;
            if (i.type === 'product' && String(i.restaurant_id) === restaurantId) return false;
            return true;
          });
        } else {
          this.archiveItems = this.archiveItems.filter(
            i => !(i.type === item.type && String(i.id) === String(item.id))
          );
        }
        this.persistArchiveToStorage();

        if (item.type === 'restaurant') {
          await this.loadRestaurants();
          await this.loadProducts();
          const dishesCount = Number(data?.products_restored ?? item.products_count ?? 0);
          const dishesPart = dishesCount > 0
            ? ` Вместе с ним восстановлено блюд: ${dishesCount}.`
            : '';
          this.showRestoreNotice(
            'Ресторан восстановлен',
            `«${item.title}» возвращён из архива в каталог.${dishesPart}`
          );
        } else if (item.type === 'order') {
          await this.loadOrders();
          this.showRestoreNotice(
            'Заказ восстановлен',
            `«${item.title}» возвращён в список заказов.`
          );
        } else {
          await this.loadProducts();
          this.showRestoreNotice(
            'Товар восстановлен',
            `«${item.title}» возвращён из архива в меню.`
          );
        }
      } catch (e) {
        alert(e?.response?.data?.error || e?.message || 'Не удалось восстановить');
        await this.loadArchive();
      }
    },
    deleteRestaurant(id) {
      this.openConfirmModal({
        title: 'Удалить ресторан?',
        message: 'Ресторан и его блюда будут перемещены в архив. Их можно восстановить в любое время во вкладке «Рестораны».',
        action: () => this.performDeleteRestaurant(id),
      });
    },
    async performDeleteRestaurant(id) {
      try {
        const http = await getLaravelApi();
        const res = await http.delete(`/api/admin/restaurants/${id}`);
        if (res.status >= 400 || res.data?.error) throw new Error(res.data?.error || 'delete failed');

        this.restaurants = this.restaurants.filter(r => String(r.id) !== String(id));
        this.products = this.products.filter(p => String(p.restaurant_id) !== String(id));
        if (this.editingRestaurant?.id === id) this.restaurantFormOpen = false;
        this.applyDeleteResponse(res.data);
      } catch (e) {
        console.error(e);
        alert(e?.response?.data?.error || 'Ошибка удаления ресторана');
      }
    },
    deleteProduct(id) {
      this.openConfirmModal({
        title: 'Удалить товар?',
        message: 'Товар будет перемещён в архив. Его можно восстановить в любое время во вкладке «Товары».',
        action: () => this.performDeleteProduct(id),
      });
    },
    async performDeleteProduct(id) {
      try {
        const http = await getLaravelApi();
        const res = await http.delete(`/api/admin/products/${id}`);
        if (res.status >= 400 || res.data?.error) throw new Error(res.data?.error || 'delete failed');

        this.products = this.products.filter(p => String(p.id) !== String(id));
        if (this.editingProduct?.id === id) this.productFormOpen = false;
        this.applyDeleteResponse(res.data);
      } catch (e) {
        console.error(e);
        alert(e?.response?.data?.error || 'Ошибка удаления товара');
      }
    },
    async deleteFeedback(id) {
      if (!confirm('Удалить сообщение?')) return;
      try {
        const http = await getLaravelApi();
        await http.delete(`/api/admin/feedback/${id}`);
        this.feedbackList = this.feedbackList.filter(m => m.id !== id);
      } catch {
        alert('Ошибка удаления');
      }
    },
    startEditReview(rev) {
      this.editingReviewId = rev.id;
      this.reviewEdit = {
        author_name: rev.author_name,
        rating: rev.rating,
        comment: rev.comment || '',
      };
    },
    async saveReviewEdit(id) {
      try {
        const http = await getLaravelApi();
        const rev = this.findReviewById(id);
        const payload = {
          rating: this.reviewEdit.rating,
          comment: this.reviewEdit.comment,
        };
        if (rev?.source === 'restaurant') {
          await http.patch(`/api/admin/restaurant-reviews/${id}`, payload);
        } else {
          await http.patch(`/api/admin/reviews/${id}`, payload);
        }
        this.editingReviewId = null;
        await this.loadReviewsGrouped();
      } catch {
        alert('Ошибка сохранения отзыва');
      }
    },
    findReviewById(id) {
      for (const group of this.reviewsGrouped) {
        for (const dish of group.dishes || []) {
          const found = (dish.reviews || []).find(r => r.id === id);
          if (found) return found;
        }
      }
      return null;
    },
    async deleteReview(rev) {
      if (!confirm('Удалить отзыв?')) return;
      try {
        const http = await getLaravelApi();
        if (rev.source === 'restaurant') {
          await http.delete(`/api/admin/restaurant-reviews/${rev.id}`);
        } else {
          await http.delete(`/api/admin/reviews/${rev.id}`);
        }
        await this.loadReviewsGrouped();
      } catch {
        alert('Ошибка удаления');
      }
    },
  },
  watch: {
    allowed(val) {
      if (val) {
        this.loadOrders();
        this.loadRestaurants();
        this.loadArchive();
      }
    },
  },
  async mounted() {
    await this.checkAccess();
    this.archiveItems = this.mergeArchiveLists(this.readArchiveFromStorage());
    this.loading = false;
  },
};
</script>

<style scoped>
.admin-page { background: #fefaf5; min-height: 100vh; padding: 2rem 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.container { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; }
h1 { font-size: 28px; margin-bottom: 1.5rem; }
.status-msg { padding: 2rem; text-align: center; color: #888; }
.status-msg.error { color: #e74c3c; }
.tabs { display: flex; gap: 8px; margin-bottom: 1.5rem; flex-wrap: wrap; }
.tabs button { padding: 10px 20px; border: 2px solid #eee; background: #fff; border-radius: 40px; cursor: pointer; font-weight: 600; font-size: 14px; }
.tabs button.active { background: #ff6b00; color: #fff; border-color: #ff6b00; }
.panel { background: #fff; border-radius: 20px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
select { padding: 6px 10px; border-radius: 8px; border: 1px solid #ddd; }
.add-btn { margin-bottom: 1rem; padding: 10px 20px; background: #ff6b00; color: #fff; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
.product-form { background: #fefaf5; padding: 1.25rem; border-radius: 16px; margin-bottom: 1.5rem; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.form-grid label { display: block; }
.form-grid input, .form-grid textarea, .form-select { padding: 10px; border: 2px solid #eee; border-radius: 10px; width: 100%; box-sizing: border-box; }
.full-width { grid-column: 1 / -1; }
.field-label { display: block; font-size: 13px; color: #666; margin-bottom: 4px; }
.form-actions { margin-top: 12px; display: flex; gap: 10px; }
.form-actions button { padding: 10px 20px; background: #ff6b00; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
.form-actions .cancel { background: #fff; color: #666; border: 2px solid #eee; }
.edit-link { background: none; border: none; color: #ff6b00; cursor: pointer; font-weight: 600; margin-right: 8px; }
.actions-cell { white-space: nowrap; }
.danger-btn { background: #fff; border: 1px solid #e74c3c; color: #e74c3c; padding: 4px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; }
.feedback-list { display: flex; flex-direction: column; gap: 12px; }
.feedback-card { padding: 1rem; border: 1px solid #f0f0f0; border-radius: 12px; }
.feedback-header { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
.feedback-email,
.feedback-phone { color: #888; font-size: 14px; }
.support-message { margin: 0 0 8px; line-height: 1.5; white-space: pre-wrap; }
.feedback-header .danger-btn { margin-left: auto; }
.reviews-grouped { display: flex; flex-direction: column; gap: 24px; }
.reviews-toolbar { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; }
.reviews-toolbar select { padding: 6px 10px; border: 1px solid #ddd; border-radius: 8px; }
.checkout-tabs-wrap { margin-bottom: 16px; }
.checkout-tabs-hint { font-size: 14px; color: #888; margin: 0 0 10px; }
.checkout-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.checkout-tab {
  padding: 10px 18px;
  border: 2px solid #eee;
  border-radius: 40px;
  background: #fff;
  font-size: 14px;
  font-weight: 600;
  color: #666;
  cursor: pointer;
}
.checkout-tab:hover { border-color: #ff6b00; color: #ff6b00; }
.checkout-tab.active { background: #ff6b00; border-color: #ff6b00; color: #fff; }
.type-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
  background: #fff3e6;
  color: #ff6b00;
  margin-left: 8px;
}
.type-badge--restaurant { background: #eef6ff; color: #2d6cdf; }
.panel-toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
}
.add-btn--inline {
  margin-bottom: 0;
}
.archive-open-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: 2px solid #eee;
  background: #fff;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  color: #555;
  cursor: pointer;
}
.archive-open-btn:hover {
  border-color: #ff6b00;
  color: #ff6b00;
}
.archive-badge {
  font-size: 12px;
  font-weight: 700;
  background: #fff3e6;
  color: #ff6b00;
  padding: 2px 8px;
  border-radius: 20px;
  min-width: 1.25rem;
  text-align: center;
}
.group-title { margin: 0 0 12px; font-size: 20px; color: #1e1e1e; padding-bottom: 8px; border-bottom: 2px solid #ff6b00; }
.dish-group { margin-bottom: 16px; padding-left: 12px; }
.dish-group h4 { margin: 0 0 10px; color: #555; font-size: 16px; }
.review-admin-card { background: #fefaf5; padding: 12px; border-radius: 12px; margin-bottom: 8px; }
.review-admin-header { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 6px; }
.stars { color: #ff6b00; }
.review-btns { margin-left: auto; display: flex; gap: 8px; }
.review-edit-author { margin: 0 0 10px; font-size: 14px; color: #555; }
.review-edit-field { display: block; margin-bottom: 10px; }
.review-edit-field .field-label { display: block; font-size: 13px; color: #666; margin-bottom: 4px; }
.review-admin-card input, .review-admin-card textarea, .review-admin-card select {
  width: 100%; padding: 8px; border: 1px solid #eee; border-radius: 8px; box-sizing: border-box;
}
.mini-actions { display: flex; gap: 8px; }
.mini-actions button { padding: 6px 14px; background: #ff6b00; color: #fff; border: none; border-radius: 8px; cursor: pointer; }
.mini-actions .cancel { background: #fff; color: #666; border: 1px solid #eee; }
.pager { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 8px; }
.pager button { border: 1px solid #ddd; background: #fff; border-radius: 8px; padding: 4px 10px; cursor: pointer; }
</style>
