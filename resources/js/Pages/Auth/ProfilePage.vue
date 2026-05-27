<template>
  <div>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>
    <HeaderComponent />
    <div class="profile-page">
      <div class="container" :class="{ 'container--wide': profileTab === 'reviews' }">
        <h1>Личный кабинет</h1>
        <div class="quick-links">
          <a href="/check" class="quick-link">Корзина</a>
          <a href="/restaurans" class="quick-link">К ресторанам</a>
        </div>

        <div v-if="loading" class="status-msg">Загрузка...</div>

        <template v-else>
          <div class="profile-card">
            <div class="profile-info">
              <h2>{{ fullName || 'Пользователь' }}</h2>
              <p>{{ email }}</p>
              <p>{{ phone || 'Телефон не указан' }}</p>
              <p>{{ address || 'Адрес не указан' }}</p>
              <p v-if="birthDate" class="birth-preview">Дата рождения: {{ formatBirthDate(birthDate) }}</p>
              <p v-if="paymentCard" class="payment-preview">Карта: {{ paymentCard }}<span v-if="paymentExpiry"> · {{ paymentExpiry }}</span></p>
            </div>
            <button @click="editMode = !editMode" class="edit-btn">
              {{ editMode ? 'Отмена' : 'Редактировать' }}
            </button>
          </div>

          <div v-if="editMode" class="edit-form">
            <h3 class="form-section-title">Контакты</h3>
            <div class="form-group">
              <label>Имя</label>
              <input v-model="fullName" type="text" placeholder="Ваше имя" />
            </div>
            <div class="form-group">
              <label>Телефон</label>
              <input v-model="phone" type="tel" placeholder="+7 (___) ___-__-__" @input="onPhoneInput" />
            </div>
            <div class="form-group">
              <label>Адрес доставки</label>
              <input v-model="address" type="text" placeholder="Улица, дом, квартира" />
            </div>
            <div class="form-group">
              <label>Дата рождения</label>
              <input
                v-if="!birthDateLocked"
                v-model="birthDate"
                type="date"
                :max="todayDate"
              />
              <input
                v-else
                :value="formatBirthDate(savedBirthDate)"
                type="text"
                readonly
                disabled
                class="input-locked"
              />
              <p v-if="birthDateLocked" class="form-hint form-hint--locked">
                Дата рождения установлена и не может быть изменена
              </p>
              <p v-else class="form-hint">
                Указывается один раз. В день рождения действует скидка 10% на заказ
              </p>
            </div>

            <h3 class="form-section-title">Способ оплаты</h3>
            <p class="form-hint">Сохранится в профиле и подставится при оформлении заказа</p>
            <div class="form-group">
              <label>Номер карты</label>
              <input v-model="paymentCard" type="text" placeholder="0000 0000 0000 0000" maxlength="19" @input="formatPaymentCard" />
            </div>
            <div class="form-row payment-card-details">
              <div class="form-group">
                <label>Срок</label>
                <input v-model="paymentExpiry" type="text" placeholder="ММ/ГГ" maxlength="5" @input="formatPaymentExpiry" />
              </div>
              <div class="form-group">
                <label>CVC</label>
                <input
                  v-model="paymentCvc"
                  type="password"
                  placeholder="CVC"
                  maxlength="3"
                  @input="formatPaymentCvc"
                />
              </div>
            </div>

            <button @click="requestSaveProfile" class="save-btn" :disabled="saving">{{ saving ? 'Сохранение...' : 'Сохранить' }}</button>
          </div>

          <div class="profile-tabs">
            <button type="button" :class="{ active: profileTab === 'orders' }" @click="switchProfileTab('orders')">Мои заказы</button>
            <button type="button" :class="{ active: profileTab === 'reviews' }" @click="switchProfileTab('reviews')">Мои отзывы</button>
          </div>

          <section v-if="profileTab === 'orders'" class="profile-section">
            <div v-if="orders.length === 0" class="empty-orders">
              <p>У вас пока нет заказов</p>
              <a href="/restaurans" class="order-link">Перейти к ресторанам</a>
            </div>
            <div v-else class="orders-list">
              <div v-for="order in orders" :key="order.id" class="order-card">
                <div class="order-header" @click="toggleOrder(order.id)">
                  <span>Заказ #{{ order.id }}</span>
                  <span :class="['order-status', statusClass(order.status)]">{{ statusText(order.status) }}</span>
                </div>
                <div class="order-total">{{ order.total_amount }} ₽</div>
                <div class="order-date">{{ formatDate(order.created_at) }}</div>
                <p v-if="order.delivery_address" class="order-address">
                  <img
                    v-if="settings.location_icon"
                    :src="settings.location_icon"
                    alt=""
                    class="order-address-icon"
                    @error="onLocationIconError"
                  />
                  <span class="order-address-text">{{ order.delivery_address }}</span>
                </p>

                <button type="button" class="toggle-items-btn" @click="toggleOrder(order.id)">
                  {{ expandedOrders[order.id] ? 'Скрыть состав' : 'Показать состав' }}
                </button>

                <div v-if="expandedOrders[order.id]" class="order-items">
                  <template v-if="getOrderItemGroups(order).length">
                    <div
                      v-for="(group, gIdx) in getOrderItemGroups(order)"
                      :key="`${order.id}-${group.restaurantId ?? gIdx}`"
                      class="order-restaurant-group"
                    >
                      <h4 class="order-restaurant-title">{{ group.title }}</h4>
                      <div v-for="(item, idx) in group.items" :key="idx" class="order-item-row">
                        <span class="item-name">{{ item.name }}</span>
                        <span class="item-qty">{{ item.quantity }} × {{ item.price }} ₽</span>
                        <span class="item-sum">{{ (item.price || 0) * (item.quantity || 1) }} ₽</span>
                      </div>
                    </div>
                  </template>
                  <p v-else class="no-items">Состав заказа недоступен</p>
                  <div v-if="getOrderItems(order).length" class="order-fees-summary">
                    <div class="order-fee-row">
                      <span>Блюда</span>
                      <span>{{ orderItemsSubtotal(order) }} ₽</span>
                    </div>
                    <div class="order-fee-row">
                      <span>Доставка</span>
                      <span>{{ formatDeliveryFee(orderDeliveryFee(order)) }}</span>
                    </div>
                    <div class="order-fee-row">
                      <span>Сервисный сбор</span>
                      <span>{{ orderServiceFee }} ₽</span>
                    </div>
                  </div>
                </div>

                <p v-if="canModifyOrder(order)" class="order-window-hint">
                  Можно изменить или отменить ещё {{ formatCountdown(secondsRemaining(order)) }}
                </p>

                <div v-if="canModifyOrder(order)" class="order-actions">
                  <button
                    v-if="canEditOrder(order)"
                    type="button"
                    class="order-action-btn order-action-btn--edit"
                    :disabled="orderActionLoading === order.id"
                    @click="openEditOrder(order)"
                  >
                    Изменить заказ
                  </button>
                  <button
                    type="button"
                    class="order-action-btn order-action-btn--cancel"
                    :disabled="orderActionLoading === order.id"
                    @click="askCancelOrder(order)"
                  >
                    Отменить заказ
                  </button>
                </div>
                <p v-else-if="order.status === 'new' && !canModifyOrder(order)" class="order-window-expired">
                  Время на изменение или отмену (5 минут) истекло
                </p>
              </div>
            </div>
          </section>

          <section v-else class="profile-section panel">
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
            <div v-else-if="!filteredReviewGroups.length" class="status-msg">У вас пока нет отзывов</div>
            <div v-else class="reviews-grouped">
              <div v-for="group in pagedReviewGroups" :key="group.restaurant_id" class="restaurant-group">
                <h3 class="group-title">🍽 {{ group.restaurant_title }}</h3>
                <div v-for="dish in group.dishes" :key="dish.product_id ?? 'restaurant'" class="dish-group">
                  <h4>
                    {{ dish.product_name }}
                    <span v-if="dish.review_type === 'dish'" class="type-badge">Блюдо</span>
                    <span v-else class="type-badge type-badge--restaurant">Ресторан</span>
                  </h4>
                  <ReviewCardUser
                    v-for="rev in dish.reviews"
                    :key="rev.id"
                    :review="rev"
                    :can-edit="true"
                    :editing="editingReviewId === rev.id"
                    :edit-rating="reviewEdit.rating"
                    :edit-comment="reviewEdit.comment"
                    :redact-icon="settings.redact_icon"
                    @edit="startEditReview(rev)"
                    @save="saveReviewEdit(rev.id)"
                    @cancel="editingReviewId = null"
                    @delete="deleteReview(rev)"
                    @update:editRating="reviewEdit.rating = $event"
                    @update:editComment="reviewEdit.comment = $event"
                  />
                </div>
              </div>
              <div class="pager" v-if="reviewsTotalPages > 1">
                <button :disabled="reviewsPage <= 1" @click="reviewsPage--">←</button>
                <span>Страница {{ reviewsPage }} / {{ reviewsTotalPages }}</span>
                <button :disabled="reviewsPage >= reviewsTotalPages" @click="reviewsPage++">→</button>
              </div>
            </div>
          </section>

          <button type="button" @click="logout" class="logout-btn">Выйти</button>
          <button type="button" @click="showDeleteModal = true" class="delete-profile-btn">Удалить профиль</button>
        </template>
      </div>
    </div>

    <div v-if="editOrderModal.show" class="confirm-modal-overlay" @click="closeEditOrder">
      <div class="confirm-modal edit-order-modal" @click.stop>
        <h3>Изменить заказ #{{ editOrderModal.orderId }}</h3>
        <p class="edit-order-hint">Осталось {{ formatCountdown(editOrderSecondsLeft) }} на сохранение</p>

        <div class="form-group">
          <label>Имя</label>
          <input v-model="editOrderForm.customerName" type="text" />
        </div>
        <div class="form-group">
          <label>Телефон</label>
          <input v-model="editOrderForm.customerPhone" type="tel" @input="onEditPhoneInput" />
        </div>
        <div class="form-group">
          <label>Адрес доставки</label>
          <input v-model="editOrderForm.deliveryAddress" type="text" />
        </div>
        <div class="form-group">
          <label>Комментарий</label>
          <textarea v-model="editOrderForm.orderComment" rows="2"></textarea>
        </div>

        <h4 class="edit-items-title">Состав</h4>
        <div v-for="(item, idx) in editOrderForm.items" :key="item.id ?? idx" class="edit-order-item">
          <span class="edit-item-name">{{ item.name }}</span>
          <div class="qty-controls">
            <button type="button" @click="changeEditItemQty(idx, -1)" :disabled="item.quantity <= 1">−</button>
            <span>{{ item.quantity }}</span>
            <button type="button" @click="changeEditItemQty(idx, 1)">+</button>
          </div>
          <span class="edit-item-sum">{{ (item.price || 0) * item.quantity }} ₽</span>
          <button
            v-if="editOrderForm.items.length > 1"
            type="button"
            class="remove-item-btn"
            @click="removeEditItem(idx)"
          >
            Удалить
          </button>
        </div>

        <div class="edit-order-totals">
          <div class="order-fee-row">
            <span>Блюда</span>
            <span>{{ editItemsSubtotal }} ₽</span>
          </div>
          <div class="order-fee-row">
            <span>Доставка</span>
            <span :class="{ 'fee-free': editDeliveryFee === 0 }">{{ formatDeliveryFee(editDeliveryFee) }}</span>
          </div>
          <p v-if="editDeliveryHint" class="edit-delivery-hint">{{ editDeliveryHint }}</p>
          <div class="order-fee-row">
            <span>Сервисный сбор</span>
            <span>{{ orderServiceFee }} ₽</span>
          </div>
          <div class="edit-order-total">Итого: {{ editOrderTotal }} ₽</div>
        </div>

        <p v-if="editOrderError" class="delete-error">{{ editOrderError }}</p>

        <div class="confirm-modal-actions">
          <button type="button" class="confirm-cancel" @click="closeEditOrder">Отмена</button>
          <button
            type="button"
            class="confirm-save"
            :disabled="editOrderSaving"
            @click="saveOrderEdit"
          >
            {{ editOrderSaving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="confirmCancelModal.show" class="confirm-modal-overlay" @click="confirmCancelModal.show = false">
      <div class="confirm-modal" @click.stop>
        <h3>Отменить заказ?</h3>
        <p>Заказ №{{ confirmCancelModal.orderId }} будет отменён. После подтверждения средства вернутся на вашу карту в ближайшее время.</p>
        <div class="confirm-modal-actions">
          <button type="button" class="confirm-cancel" @click="confirmCancelModal.show = false">Назад</button>
          <button
            type="button"
            class="confirm-danger"
            :disabled="orderActionLoading === confirmCancelModal.orderId"
            @click="performCancelOrder"
          >
            {{ orderActionLoading === confirmCancelModal.orderId ? 'Отмена...' : 'Да, отменить' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="orderNoticeModal.show" class="confirm-modal-overlay" @click="closeOrderNotice">
      <div class="confirm-modal order-notice-modal" @click.stop>
        <div class="notice-icon" :class="orderNoticeModal.variant">{{ orderNoticeModal.icon }}</div>
        <h3>{{ orderNoticeModal.title }}</h3>
        <p>{{ orderNoticeModal.message }}</p>
        <button type="button" class="confirm-save notice-ok-btn" @click="closeOrderNotice">Понятно</button>
      </div>
    </div>

    <div v-if="confirmBirthdayModal.show" class="confirm-modal-overlay" @click="confirmBirthdayModal.show = false">
      <div class="confirm-modal" @click.stop>
        <h3>Установить дату рождения?</h3>
        <p>
          Вы указываете дату: <strong>{{ formatBirthDate(confirmBirthdayModal.date) }}</strong>.
        </p>
        <p class="birthday-confirm-warning">
          После сохранения изменить дату рождения будет невозможно. Проверьте, что дата указана верно.
        </p>
        <div class="confirm-modal-actions">
          <button type="button" class="confirm-cancel" @click="confirmBirthdayModal.show = false">Изменить</button>
          <button type="button" class="confirm-save" :disabled="saving" @click="confirmBirthdayAndSave">
            {{ saving ? 'Сохранение...' : 'Подтвердить и сохранить' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="profileSavedModal.show" class="confirm-modal-overlay" @click="profileSavedModal.show = false">
      <div class="confirm-modal order-notice-modal" @click.stop>
        <div class="notice-icon success">✓</div>
        <h3>Профиль сохранён</h3>
        <p>Изменения успешно сохранены.</p>
        <button type="button" class="confirm-save notice-ok-btn" @click="profileSavedModal.show = false">Понятно</button>
      </div>
    </div>

    <div v-if="showDeleteModal" class="confirm-modal-overlay" @click="showDeleteModal = false">
      <div class="confirm-modal" @click.stop>
        <h3>Удалить профиль?</h3>
        <p>Будут безвозвратно удалены все ваши данные: заказы, отзывы, профиль и учётная запись.</p>
        <div class="form-group delete-password-group">
          <label for="delete-password">Пароль для подтверждения</label>
          <input
            id="delete-password"
            v-model="deletePassword"
            type="password"
            placeholder="Введите пароль от аккаунта"
            autocomplete="current-password"
          />
        </div>
        <p v-if="deleteError" class="delete-error">{{ deleteError }}</p>
        <div class="confirm-modal-actions">
          <button type="button" class="confirm-cancel" @click="showDeleteModal = false">Отмена</button>
          <button type="button" class="confirm-danger" :disabled="deletingProfile" @click="deleteProfile">
            {{ deletingProfile ? 'Удаление...' : 'Удалить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import HeaderComponent from '../../../Components/HeaderComponent.vue';
import ReviewCardUser from '../../Components/ReviewCardUser.vue';
import { usePageSeo } from '../../usePageSeo.js';
import {
  supabase,
  getAuthApi,
  getCurrentUser,
  getCurrentSession,
  loadProfile,
  ensureProfile,
  getLaravelApi,
  formatPhoneDisplay,
  formatPhoneInput,
} from '../../supabase.js';
import { clearCartForLogout } from '../../cart.js';
import {
  parseOrderItems,
  groupOrderItemsByRestaurant,
  canModifyOrder,
  canEditOrder,
  secondsRemaining,
  formatCountdown,
  calcItemsTotal,
  calcOrderTotal,
  ORDER_SERVICE_FEE,
} from '../../orderUtils.js';
import { calcDeliveryFee, formatDeliveryFee, amountUntilFreeDelivery } from '../../deliveryFee.js';
import { loadSiteSettings, applySiteSettings } from '../../settingsCache.js';

export default {
  name: 'ProfilePage',
  components: { HeaderComponent, Head, ReviewCardUser },
  setup() {
    const seo = usePageSeo('Личный кабинет — Лопать Подано', 'История заказов, адреса доставки, отзывы и настройки профиля Лопать Подано.');
    return { seo };
  },
  data() {
    return {
      loading: true,
      saving: false,
      profileTab: 'orders',
      fullName: '',
      email: '',
      phone: '',
      address: '',
      paymentCard: '',
      paymentExpiry: '',
      paymentCvc: '',
      birthDate: '',
      savedBirthDate: '',
      editMode: false,
      confirmBirthdayModal: { show: false, date: '' },
      profileSavedModal: { show: false },
      showDeleteModal: false,
      deletingProfile: false,
      deletePassword: '',
      deleteError: '',
      orders: [],
      expandedOrders: {},
      userId: null,
      reviewsGrouped: [],
      reviewsLoading: false,
      editingReviewId: null,
      reviewEdit: { rating: 5, comment: '' },
      reviewsSort: 'new',
      reviewsPage: 1,
      reviewsPageSize: 1,
      reviewsRestaurantTab: 'all',
      reviewsTypeFilter: 'all',
      reviewRestaurants: [],
      settings: { location_icon: '', redact_icon: '' },
      nowTick: Date.now(),
      orderTickTimer: null,
      orderActionLoading: null,
      editOrderModal: { show: false, orderId: null },
      editOrderForm: {
        customerName: '',
        customerPhone: '',
        deliveryAddress: '',
        orderComment: '',
        items: [],
      },
      editOrderSaving: false,
      editOrderError: '',
      orderServiceFee: ORDER_SERVICE_FEE,
      confirmCancelModal: { show: false, orderId: null },
      orderNoticeModal: {
        show: false,
        variant: 'success',
        icon: '✓',
        title: '',
        message: '',
      },
    };
  },
  computed: {
    birthDateLocked() {
      return !!this.savedBirthDate;
    },
    todayDate() {
      return new Date().toISOString().slice(0, 10);
    },
    editItemsSubtotal() {
      return calcItemsTotal(this.editOrderForm.items);
    },
    editDeliveryFee() {
      return calcDeliveryFee(this.editItemsSubtotal);
    },
    editDeliveryHint() {
      const left = amountUntilFreeDelivery(this.editItemsSubtotal);
      if (left <= 0) return '';
      return `Добавьте блюд ещё на ${left} ₽ — доставка станет бесплатной`;
    },
    editOrderTotal() {
      return calcOrderTotal(this.editOrderForm.items);
    },
    editOrderSecondsLeft() {
      const order = this.orders.find(o => o.id === this.editOrderModal.orderId);
      return order ? secondsRemaining(order, this.nowTick) : 0;
    },
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
  },
  methods: {
    async switchProfileTab(name) {
      this.profileTab = name;
      if (name === 'reviews') {
        const tasks = [];
        if (!this.reviewRestaurants.length) tasks.push(this.loadReviewRestaurants());
        if (!this.reviewsGrouped.length) tasks.push(this.loadReviewsGrouped());
        if (tasks.length) await Promise.all(tasks);
      }
    },
    isPlaceholderRestaurantTitle(title) {
      return /^Ресторан #\d+$/.test(String(title || '').trim());
    },
    restaurantDisplayTitle(id, titleFromApi) {
      const fromApi = String(titleFromApi || '').trim();
      if (fromApi && !this.isPlaceholderRestaurantTitle(fromApi)) return fromApi;
      const r = this.reviewRestaurants.find(x => String(x.id) === String(id));
      const fromList = (r?.title || r?.name || '').trim();
      if (fromList) return fromList;
      return fromApi || (Number(id) > 0 ? `Ресторан #${id}` : 'Без ресторана');
    },
    async loadReviewRestaurants() {
      try {
        const http = await getLaravelApi();
        const { data } = await http.get('/api/restaurants');
        this.reviewRestaurants = Array.isArray(data) ? data : [];
      } catch {
        this.reviewRestaurants = [];
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
        !tabs.some(t => String(t.id) === String(this.reviewsRestaurantTab))
      ) {
        this.reviewsRestaurantTab = 'all';
      }
    },
    async loadReviewsGrouped() {
      this.reviewsLoading = true;
      try {
        const http = await getLaravelApi();
        const { data } = await http.get('/api/profile/reviews', { params: { sort: this.reviewsSort } });
        this.reviewsGrouped = Array.isArray(data) ? data : [];
        this.syncReviewsRestaurantTab();
      } catch (e) {
        console.error(e);
        this.reviewsGrouped = [];
      } finally {
        this.reviewsLoading = false;
      }
    },
    reloadReviews() {
      this.reviewsPage = 1;
      this.loadReviewsGrouped();
    },
    startEditReview(rev) {
      this.editingReviewId = rev.id;
      this.reviewEdit = {
        rating: rev.rating,
        comment: rev.comment || '',
      };
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
    async saveReviewEdit(id) {
      try {
        const http = await getLaravelApi();
        const rev = this.findReviewById(id);
        const payload = { rating: this.reviewEdit.rating, comment: this.reviewEdit.comment };
        if (rev?.source === 'restaurant') {
          await http.patch(`/api/profile/restaurant-reviews/${id}`, payload);
        } else {
          await http.patch(`/api/profile/reviews/${id}`, payload);
        }
        this.editingReviewId = null;
        await this.loadReviewsGrouped();
      } catch {
        alert('Ошибка сохранения отзыва');
      }
    },
    async deleteReview(rev) {
      if (!confirm('Удалить отзыв?')) return;
      try {
        const http = await getLaravelApi();
        if (rev.source === 'restaurant') {
          await http.delete(`/api/profile/restaurant-reviews/${rev.id}`);
        } else {
          await http.delete(`/api/profile/reviews/${rev.id}`);
        }
        await this.loadReviewsGrouped();
      } catch {
        alert('Ошибка удаления');
      }
    },
    onPhoneInput() {
      this.phone = formatPhoneInput(this.phone);
    },
    formatPaymentCard() {
      const n = this.paymentCard.replace(/\D/g, '').slice(0, 16);
      this.paymentCard = Array.from({ length: Math.ceil(n.length / 4) }, (_, i) =>
        n.slice(i * 4, i * 4 + 4)
      ).join(' ');
    },
    formatPaymentExpiry() {
      const n = this.paymentExpiry.replace(/\D/g, '');
      this.paymentExpiry = n.length >= 2 ? n.slice(0, 2) + '/' + n.slice(2, 4) : n;
    },
    applyProfile(profile, user) {
      this.fullName = profile?.full_name || user.user_metadata?.full_name || '';
      this.phone = formatPhoneDisplay(profile?.phone || user.user_metadata?.phone);
      this.address = profile?.address || '';
      if (profile?.payment_card) {
        const digits = String(profile.payment_card).replace(/\D/g, '').slice(0, 16);
        this.paymentCard = Array.from({ length: Math.ceil(digits.length / 4) }, (_, i) =>
          digits.slice(i * 4, i * 4 + 4)
        ).join(' ');
      } else {
        this.paymentCard = '';
      }
      this.paymentExpiry = profile?.payment_expiry || '';
      this.paymentCvc = profile?.payment_cvc
        ? String(profile.payment_cvc).replace(/\D/g, '').slice(0, 3)
        : '';
      const birth = profile?.birth_date ? String(profile.birth_date).slice(0, 10) : '';
      this.savedBirthDate = birth;
      this.birthDate = birth;
    },
    formatBirthDate(value) {
      if (!value) return '';
      const d = new Date(value);
      if (Number.isNaN(d.getTime())) return value;
      return d.toLocaleDateString('ru-RU');
    },
    formatPaymentCvc() {
      this.paymentCvc = this.paymentCvc.replace(/\D/g, '').slice(0, 3);
    },
    async loadProfileData() {
      const session = await getCurrentSession();
      const user = session?.user ?? (await getCurrentUser());
      if (!user) {
        window.location.href = '/auth?redirect=/profile';
        return;
      }
      this.userId = user.id;
      this.email = user.email || '';

      await ensureProfile(user);
      const profile = await loadProfile(user.id);
      this.applyProfile(profile, user);
    },
    async loadOrders() {
      if (!this.userId) return;
      const authApi = await getAuthApi();
      const { data } = await authApi.get('/orders', {
        params: { user_id: `eq.${this.userId}`, select: '*', order: 'created_at.desc' },
      });
      this.orders = data || [];
    },
    requestSaveProfile() {
      if (!this.birthDateLocked && this.birthDate) {
        this.confirmBirthdayModal = { show: true, date: this.birthDate };
        return;
      }
      this.performSaveProfile();
    },
    confirmBirthdayAndSave() {
      this.confirmBirthdayModal.show = false;
      this.performSaveProfile();
    },
    buildProfilePayload() {
      const payload = {
        full_name: this.fullName,
        phone: this.phone.replace(/\D/g, ''),
        address: this.address.trim(),
        payment_card: this.paymentCard.replace(/\D/g, ''),
        payment_expiry: this.paymentExpiry,
        payment_cvc: this.paymentCvc.replace(/\D/g, '').slice(0, 3),
      };
      if (!this.birthDateLocked && this.birthDate) {
        payload.birth_date = this.birthDate;
      }
      return payload;
    },
    async performSaveProfile() {
      this.saving = true;
      try {
        const http = await getLaravelApi();
        const payload = this.buildProfilePayload();
        const { status, data } = await http.patch('/api/profile', payload);
        if (status >= 400) {
          throw new Error(data?.error || 'save failed');
        }

        const authApi = await getAuthApi();
        try {
          await authApi.patch('/profiles', payload, { params: { id: `eq.${this.userId}` } });
        } catch {
          /* Laravel API уже сохранил через service role */
        }

        this.editMode = false;
        const profile = await loadProfile(this.userId);
        const user = await getCurrentUser();
        if (user) this.applyProfile(profile, user);
        this.profileSavedModal.show = true;
      } catch (e) {
        console.error(e);
        alert(e?.response?.data?.error || e?.message || 'Ошибка сохранения профиля');
      } finally {
        this.saving = false;
      }
    },
    getOrderItems(order) {
      return parseOrderItems(order);
    },
    getOrderItemGroups(order) {
      return groupOrderItemsByRestaurant(order).map(g => ({
        ...g,
        title: this.restaurantDisplayTitle(g.restaurantId, null),
      }));
    },
    canModifyOrder(order) {
      return canModifyOrder(order, this.nowTick);
    },
    canEditOrder(order) {
      return canEditOrder(order, this.nowTick);
    },
    secondsRemaining(order) {
      return secondsRemaining(order, this.nowTick);
    },
    formatCountdown,
    formatDeliveryFee,
    orderItemsSubtotal(order) {
      return calcItemsTotal(parseOrderItems(order));
    },
    orderDeliveryFee(order) {
      return calcDeliveryFee(this.orderItemsSubtotal(order));
    },
    toggleOrder(id) {
      this.expandedOrders[id] = !this.expandedOrders[id];
    },
    askCancelOrder(order) {
      if (!this.canModifyOrder(order)) {
        alert('Время на отмену истекло');
        return;
      }
      this.confirmCancelModal = { show: true, orderId: order.id };
    },
    async performCancelOrder() {
      const orderId = this.confirmCancelModal.orderId;
      const order = this.orders.find(o => o.id === orderId);
      if (!order || !this.canModifyOrder(order)) {
        this.confirmCancelModal.show = false;
        alert('Время на отмену истекло');
        return;
      }
      this.orderActionLoading = orderId;
      try {
        const http = await getLaravelApi();
        const { data } = await http.post(`/api/profile/orders/${orderId}/cancel`);
        const updated = data?.order || { ...order, status: 'cancelled' };
        this.orders = this.orders.map(o =>
          (o.id === orderId ? { ...o, ...updated, status: 'cancelled' } : o)
        );
        this.confirmCancelModal.show = false;
        this.orderNoticeModal = {
          show: true,
          variant: 'cancel',
          icon: '↩',
          title: 'Заказ отменён',
          message: 'Средства вернутся на вашу карту в ближайшее время. Статус заказа обновлён в личном кабинете.',
        };
      } catch (e) {
        alert(e?.response?.data?.error || 'Не удалось отменить заказ');
      } finally {
        this.orderActionLoading = null;
      }
    },
    closeOrderNotice() {
      this.orderNoticeModal.show = false;
    },
    openEditOrder(order) {
      if (!this.canEditOrder(order)) {
        alert('Изменить можно только заказ из одного ресторана в течение 5 минут');
        return;
      }
      this.editOrderError = '';
      this.editOrderModal = { show: true, orderId: order.id };
      this.editOrderForm = {
        customerName: order.customer_name || '',
        customerPhone: formatPhoneDisplay(order.customer_phone || ''),
        deliveryAddress: order.delivery_address || '',
        orderComment: order.comment || '',
        items: parseOrderItems(order).map(i => ({ ...i, quantity: Number(i.quantity) || 1 })),
      };
    },
    closeEditOrder() {
      this.editOrderModal = { show: false, orderId: null };
      this.editOrderError = '';
    },
    changeEditItemQty(index, delta) {
      const item = this.editOrderForm.items[index];
      if (!item) return;
      item.quantity = Math.max(1, (Number(item.quantity) || 1) + delta);
    },
    removeEditItem(index) {
      if (this.editOrderForm.items.length <= 1) return;
      this.editOrderForm.items.splice(index, 1);
    },
    onEditPhoneInput() {
      this.editOrderForm.customerPhone = formatPhoneInput(this.editOrderForm.customerPhone);
    },
    async saveOrderEdit() {
      const order = this.orders.find(o => o.id === this.editOrderModal.orderId);
      if (!order || !this.canEditOrder(order)) {
        this.editOrderError = 'Время на изменение истекло';
        return;
      }
      if (!this.editOrderForm.deliveryAddress.trim()) {
        this.editOrderError = 'Укажите адрес доставки';
        return;
      }
      this.editOrderSaving = true;
      this.editOrderError = '';
      try {
        const http = await getLaravelApi();
        const { data } = await http.patch(`/api/profile/orders/${order.id}`, {
          items: this.editOrderForm.items,
          total: this.editOrderTotal,
          customerName: this.editOrderForm.customerName.trim(),
          customerPhone: this.editOrderForm.customerPhone.replace(/\D/g, ''),
          deliveryAddress: this.editOrderForm.deliveryAddress.trim(),
          orderComment: this.editOrderForm.orderComment.trim(),
        });
        const updated = data?.order;
        if (updated) {
          this.orders = this.orders.map(o => (o.id === order.id ? { ...o, ...updated } : o));
        }
        this.closeEditOrder();
        this.orderNoticeModal = {
          show: true,
          variant: 'success',
          icon: '✓',
          title: 'Заказ изменён',
          message: 'Изменения сохранены. Сумма и состав заказа обновлены — доставка пересчитана автоматически.',
        };
      } catch (e) {
        this.editOrderError = e?.response?.data?.error || 'Не удалось сохранить изменения';
      } finally {
        this.editOrderSaving = false;
      }
    },
    async deleteProfile() {
      this.deleteError = '';
      if (!this.deletePassword.trim()) {
        this.deleteError = 'Введите пароль для подтверждения';
        return;
      }
      this.deletingProfile = true;
      try {
        const http = await getLaravelApi();
        const { status, data } = await http.delete('/api/profile', {
          data: { password: this.deletePassword },
        });
        if (status >= 400) throw new Error(data?.error || 'delete failed');
        await supabase.auth.signOut();
        clearCartForLogout();
        window.location.href = '/';
      } catch (e) {
        console.error(e);
        this.deleteError = e?.response?.data?.error || 'Не удалось удалить профиль. Проверьте пароль.';
      } finally {
        this.deletingProfile = false;
      }
    },
    async logout() {
      await supabase.auth.signOut();
      clearCartForLogout();
      window.location.href = '/';
    },
    statusClass(s) {
      return { new: 'status-new', processing: 'status-new', delivering: 'status-new', delivered: 'status-done', cancelled: 'status-cancel' }[s] || '';
    },
    statusText(s) {
      return { new: 'Новый', processing: 'Готовится', delivering: 'В пути', delivered: 'Доставлен', cancelled: 'Отменён' }[s] || s;
    },
    formatDate(d) {
      return new Date(d).toLocaleString('ru-RU');
    },
    async loadSiteSettingsData() {
      try {
        const data = await loadSiteSettings();
        applySiteSettings(this.settings, data);
      } catch (e) {
        console.error('Ошибка загрузки иконок:', e);
      }
    },
    onLocationIconError(e) {
      e.target.style.display = 'none';
    },
  },
  async mounted() {
    this.orderTickTimer = setInterval(() => {
      this.nowTick = Date.now();
    }, 1000);
    try {
      await this.loadSiteSettingsData();
      await this.loadProfileData();
      await Promise.all([this.loadReviewRestaurants(), this.loadOrders()]);
    } catch (e) {
      console.error(e);
    } finally {
      this.loading = false;
    }
  },
  beforeUnmount() {
    if (this.orderTickTimer) clearInterval(this.orderTickTimer);
  },
};
</script>

<style scoped>
.profile-page { background: #fefaf5; min-height: 100vh; padding: 2rem 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.container { max-width: 700px; margin: 0 auto; padding: 0 1.5rem; }
.container--wide { max-width: 1100px; }
h1 { font-size: 28px; font-weight: 700; color: #1e1e1e; margin-bottom: 2rem; }
.status-msg { text-align: center; padding: 2rem; color: #888; }
.quick-links { display: flex; gap: 10px; margin-bottom: 1rem; }
.quick-link { padding: 8px 14px; border-radius: 999px; border: 2px solid #eee; color: #666; text-decoration: none; font-weight: 600; font-size: 14px; background: #fff; }
.quick-link:hover { border-color: #ff6b00; color: #ff6b00; }
.profile-card { background: #fff; border-radius: 20px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; margin-bottom: 1.5rem; }
.profile-info { flex: 1; }
.profile-info h2 { margin: 0 0 4px; font-size: 20px; font-weight: 600; }
.profile-info p { margin: 2px 0; font-size: 14px; color: #888; }
.payment-preview { color: #555 !important; }
.edit-btn { padding: 10px 20px; background: #fff; border: 2px solid #eee; border-radius: 12px; cursor: pointer; font-weight: 600; }
.edit-btn:hover { border-color: #ff6b00; color: #ff6b00; }
.edit-form { background: #fff; border-radius: 20px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 16px; }
.form-section-title { margin: 8px 0 0; font-size: 16px; font-weight: 700; }
.form-hint { margin: -8px 0 0; font-size: 13px; color: #999; }
.form-hint--locked { color: #888; }
.input-locked {
  background: #f5f5f5;
  color: #666;
  cursor: not-allowed;
}
.birthday-confirm-warning {
  color: #c0392b;
  font-size: 14px;
  line-height: 1.45;
}
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.payment-card-details .form-group input { width: 100%; box-sizing: border-box; }
.form-group label { font-size: 14px; font-weight: 500; color: #666; }
.form-group input { padding: 12px 16px; border: 2px solid #eee; border-radius: 14px; font-size: 15px; background: #fafafa; }
.form-group input:focus { outline: none; border-color: #ff6b00; background: #fff; }
.save-btn { padding: 14px; background: var(--brand-gradient); color: #fff; border: none; border-radius: 14px; font-size: 16px; font-weight: 700; cursor: pointer; }
.save-btn:hover:not(:disabled) { filter: brightness(0.97); }
.save-btn:disabled { opacity: 0.6; }
.profile-tabs { display: flex; gap: 8px; margin: 1.5rem 0 1rem; flex-wrap: wrap; }
.profile-tabs button { padding: 10px 20px; border: 2px solid #eee; background: #fff; border-radius: 40px; cursor: pointer; font-weight: 600; font-size: 14px; }
.profile-tabs button.active { background: var(--brand-gradient); color: #fff; border-color: var(--brand-solid); }
.profile-section { margin-bottom: 1rem; }
.panel { background: #fff; border-radius: 20px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.empty-orders { text-align: center; padding: 3rem; background: #fff; border-radius: 20px; }
.order-link { color: var(--brand-solid); font-weight: 600; text-decoration: none; }
.orders-list { display: flex; flex-direction: column; gap: 12px; }
.order-card { background: #fff; border-radius: 16px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-weight: 600; cursor: pointer; }
.order-status { padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
.status-new { background: #fff3e0; color: #e65100; }
.status-done { background: #e8f5e9; color: #2e7d32; }
.status-cancel { background: #ffebee; color: #c62828; }
.order-total { font-size: 20px; font-weight: 700; color: var(--brand-solid); }
.order-date { font-size: 13px; color: #999; margin-top: 4px; }
.order-address {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  font-size: 13px;
  color: #666;
  margin: 8px 0 0;
}
.order-address-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  margin-top: 1px;
  object-fit: contain;
}
.order-address-text { line-height: 1.4; }
.toggle-items-btn { margin-top: 10px; background: none; border: none; color: var(--brand-solid); font-weight: 600; cursor: pointer; font-size: 14px; padding: 0; }
.order-items { margin-top: 12px; padding-top: 12px; border-top: 1px solid #f0f0f0; }
.order-restaurant-group { margin-bottom: 14px; }
.order-restaurant-group:last-child { margin-bottom: 0; }
.order-restaurant-title {
  margin: 0 0 8px;
  font-size: 15px;
  font-weight: 700;
  color: #1e1e1e;
  padding-bottom: 6px;
  border-bottom: 2px solid var(--brand-solid);
}
.order-item-row { display: grid; grid-template-columns: 1fr auto auto; gap: 10px; padding: 8px 0; border-bottom: 1px solid #fafafa; font-size: 14px; }
.order-fees-summary {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed #eee;
  font-size: 14px;
  color: #666;
}
.order-fee-row {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
}
.order-fee-row .fee-free { color: #2e7d32; font-weight: 600; }
.edit-delivery-hint {
  font-size: 12px;
  color: var(--brand-solid);
  margin: 4px 0 8px;
  line-height: 1.4;
}
.order-notice-modal { text-align: center; max-width: 400px; }
.notice-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 16px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 700;
}
.notice-icon.success { background: #e8f5e9; color: #2e7d32; }
.notice-icon.cancel { background: #eef6ff; color: #2d6cdf; }
.order-notice-modal h3 { margin: 0 0 10px; font-size: 20px; }
.order-notice-modal p { margin: 0 0 20px; color: #666; line-height: 1.5; font-size: 15px; }
.notice-ok-btn { width: 100%; }
.order-restaurant-group .order-item-row:last-child { border-bottom: none; }
.order-window-hint { margin: 12px 0 8px; font-size: 13px; color: var(--brand-solid); font-weight: 600; }
.order-window-expired { margin: 12px 0 0; font-size: 13px; color: #999; }
.order-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
.order-action-btn {
  padding: 10px 16px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: 2px solid transparent;
}
.order-action-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.order-action-btn--edit { background: #fff; border-color: var(--brand-solid); color: var(--brand-solid); }
.order-action-btn--edit:hover:not(:disabled) { background: #fff8f0; }
.order-action-btn--cancel { background: #fff; border-color: #e74c3c; color: #e74c3c; }
.order-action-btn--cancel:hover:not(:disabled) { background: #fff5f5; }
.edit-order-modal { max-width: 480px; max-height: 90vh; overflow-y: auto; }
.edit-order-hint { margin: 0 0 16px; font-size: 13px; color: var(--brand-solid); }
.edit-order-modal .form-group { margin-bottom: 12px; }
.edit-order-modal .form-group label { display: block; font-size: 13px; color: #666; margin-bottom: 4px; }
.edit-order-modal input,
.edit-order-modal textarea {
  width: 100%; padding: 10px 12px; border: 2px solid #eee; border-radius: 10px; box-sizing: border-box; font-size: 14px;
}
.edit-items-title { margin: 16px 0 10px; font-size: 15px; }
.edit-order-item {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 8px;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f5f5f5;
  font-size: 14px;
}
.edit-item-name { font-weight: 600; }
.qty-controls { display: flex; align-items: center; gap: 6px; }
.qty-controls button {
  width: 28px; height: 28px; border: 1px solid #eee; border-radius: 8px; background: #fff; cursor: pointer; font-weight: 700;
}
.remove-item-btn {
  grid-column: 1 / -1;
  justify-self: start;
  background: none;
  border: none;
  color: #e74c3c;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}
.edit-order-totals { margin-top: 14px; font-size: 14px; color: #666; line-height: 1.6; }
.edit-order-total { font-size: 18px; font-weight: 700; color: var(--brand-solid); margin-top: 4px; }
.confirm-save { flex: 1; padding: 12px; border: none; border-radius: 12px; background: var(--brand-gradient); color: #fff; font-weight: 700; cursor: pointer; }
.confirm-save:hover:not(:disabled) { filter: brightness(0.97); }
.confirm-save:disabled { opacity: 0.6; cursor: not-allowed; }
.status-cancel { background: #fdecea; color: #e74c3c; }
.item-name { font-weight: 500; }
.item-qty { color: #888; }
.item-sum { font-weight: 600; color: var(--brand-solid); }
.no-items { color: #999; font-size: 13px; margin: 0; }
.reviews-grouped { display: flex; flex-direction: column; gap: 24px; }
.reviews-toolbar { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; }
.reviews-toolbar select { padding: 6px 10px; border: 1px solid #ddd; border-radius: 8px; }
.checkout-tabs-wrap { margin-bottom: 16px; }
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
.checkout-tab:hover { border-color: var(--brand-solid); color: var(--brand-solid); }
.checkout-tab.active { background: var(--brand-gradient); border-color: var(--brand-solid); color: #fff; }
.type-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
  background: #fff3e6;
  color: var(--brand-solid);
  margin-left: 8px;
}
.type-badge--restaurant { background: #eef6ff; color: #2d6cdf; }
.group-title { margin: 0 0 12px; font-size: 20px; color: #1e1e1e; padding-bottom: 8px; border-bottom: 2px solid var(--brand-solid); }
.dish-group { margin-bottom: 16px; padding-left: 12px; }
.dish-group h4 { margin: 0 0 10px; color: #555; font-size: 16px; }
.review-admin-card { background: #fefaf5; padding: 12px; border-radius: 12px; margin-bottom: 8px; }
.review-admin-header { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 6px; }
.stars { color: var(--brand-solid); }
.review-btns { margin-left: auto; display: flex; gap: 8px; }
.review-admin-card input, .review-admin-card textarea, .review-admin-card select {
  width: 100%; margin-bottom: 8px; padding: 8px; border: 1px solid #eee; border-radius: 8px; box-sizing: border-box;
}
.mini-actions { display: flex; gap: 8px; }
.mini-actions button { padding: 6px 14px; background: var(--brand-gradient); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
.mini-actions button:hover:not(:disabled) { filter: brightness(0.97); }
.mini-actions .cancel { background: #fff; color: #666; border: 1px solid #eee; }
.edit-link { background: none; border: none; color: var(--brand-solid); cursor: pointer; font-weight: 600; }
.danger-btn { background: #fff; border: 1px solid #e74c3c; color: #e74c3c; padding: 4px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; }
.pager { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 12px; }
.pager button { padding: 8px 14px; border: 1px solid #eee; border-radius: 8px; background: #fff; cursor: pointer; }
.pager button:disabled { opacity: 0.4; cursor: default; }
.logout-btn { width: 100%; padding: 14px; background: #fff; color: #e74c3c; border: 2px solid #e74c3c; border-radius: 14px; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 2rem; }
.delete-profile-btn { width: 100%; padding: 12px; margin-top: 10px; background: #fff; color: #999; border: 2px solid #eee; border-radius: 14px; font-size: 14px; font-weight: 600; cursor: pointer; }
.delete-profile-btn:hover { border-color: #e74c3c; color: #e74c3c; }
.confirm-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 2000; padding: 1rem; }
.confirm-modal { background: #fff; border-radius: 20px; padding: 1.75rem; max-width: 400px; width: 100%; box-shadow: 0 16px 48px rgba(0,0,0,0.2); }
.confirm-modal h3 { margin: 0 0 12px; font-size: 20px; color: #1e1e1e; }
.confirm-modal p { margin: 0 0 16px; font-size: 14px; color: #666; line-height: 1.5; }
.delete-password-group { margin-bottom: 12px; }
.delete-password-group label { display: block; font-size: 13px; color: #666; margin-bottom: 6px; }
.delete-password-group input {
  width: 100%; padding: 10px 12px; border: 2px solid #eee; border-radius: 10px; box-sizing: border-box;
}
.delete-error { color: #e74c3c; font-size: 13px; margin: 0 0 12px; }
.confirm-modal-actions { display: flex; gap: 10px; }
.confirm-cancel { flex: 1; padding: 12px; border: 2px solid #eee; border-radius: 12px; background: #fff; font-weight: 600; cursor: pointer; }
.confirm-danger { flex: 1; padding: 12px; border: none; border-radius: 12px; background: #e74c3c; color: #fff; font-weight: 700; cursor: pointer; }
.confirm-danger:disabled { opacity: 0.6; cursor: not-allowed; }
.birth-preview { color: #555 !important; }
</style>
