<template>

<header>
    <div class="headerBody">
      <div class="headerLeft">
        <a href="/"><img src="../assets/icons/iconLogo.svg" alt="Logo" height="45px" width="50px" pointer-events: none></a>
          <input class="search-input"
            v-model="searchQuery"
            placeholder="Поиск..."  
          >                          
          <input class="location-input"
          v-model="searchLocation"
          placeholder="Ваш Адрес"         
          >
      </div>
                  
      <nav class="headerNav">
          <a v-if="isOnHomePage" href="#" id="headerCart" @click.prevent="toggleCartPopup"> Корзина </a>
          <a href=""> <img src="../assets/icons/profileIcon.svg" alt="Profile"> </a>
      </nav>



      <div v-if="showCartPopup" class="cart-popup-overlay" @click="closeCartPopup">
      <div class="cart-popup" @click.stop>
        <div class="cart-popup-header">
          <h3>Ваша корзина</h3>
          <button class="close-btn" @click="closeCartPopup">×</button>
        </div>
        
        <div class="cart-popup-content">
          <div v-if="allCartItems.length === 0" class="empty-cart-popup">
            <p>Корзина пуста</p>
          </div>
          
          <div v-else class="cart-items-popup">
            <!-- Группируем по ресторанам -->
            <div v-for="restaurant in groupedByRestaurant" :key="restaurant.id" class="restaurant-group">
              <div class="restaurant-header">
                <h4>{{ restaurant.name }}</h4>
                <button 
                    class="go-to-restaurant-btn" 
                    @click="goToRestaurant(restaurant.id)"
                    title="Перейти в ресторан"
                  >
                    →
                  </button>
              </div>
              
              <div class="restaurant-items">
                <div v-for="item in restaurant.items" :key="item.id" class="cart-item-popup">
                  <div class="cart-item-info">
                    <div class="item-name">{{ item.name }}</div>
                    <div class="item-details">
                      <span>{{ item.quantity }} × {{ item.price }} ₽</span>
                      <span class="item-total">{{ item.price * item.quantity }} ₽</span>
                    </div>
                  </div>
                </div>
              </div>

              
              
              <div class="restaurant-total">
                Итого по ресторану: {{ restaurant.total }} ₽
              </div>
            </div>
            
            <div class="cart-popup-summary">
              <p><strong>Общая сумма: {{ totalCartPrice }} ₽</strong></p>
              <button class="view-full-cart-btn" @click="goToCartPage">Оформить заказ</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
    
  </header>

</template>



<script>
export default {
   name: 'HeaderComponent',
  data() {
    return {
      searchQuery: '',
      searchLocation: '',
      showCartPopup: false,
      restaurants: [
        { id: 1, title: 'KFC', rating: 4 },
        { id: 2, title: 'Велопицца', rating: 5 },
        { id: 3, title: 'Вкусно и точка', rating: 3 },
        { id: 4, title: 'На лаваше', rating: 4 },
        { id: 5, title: 'Pizzaiolo', rating: 4 },
        { id: 6, title: 'Burger King', rating: 4 },
        { id: 7, title: 'Subway', rating: 4 },
        { id: 8, title: 'Starbucks', rating: 5 },
        
        { id: 10, title: 'Теремок', rating: 4 },
        { id: 11, title: 'Шаурма №1', rating: 3 },
        { id: 12, title: 'Dominos Pizza', rating: 5 },
        { id: 13, title: 'Суши Wok', rating: 4 },
        { id: 14, title: 'Kebab House', rating: 4 },
        { id: 15, title: 'Кофемания', rating: 5 },
        { id: 16, title: 'Чайхона №1', rating: 4 },
        { id: 17, title: 'Папа Джонс', rating: 4 },
        { id: 18, title: 'Вареничная №1', rating: 5 },
        { id: 19, title: 'Ванлав', rating: 4 },
        { id: 20, title: 'Арома', rating: 4 },
      ]
    }
  },
  computed: {

     isOnHomePage() {
      // Проверяем, находимся ли мы на главной странице
      // Если у вашей главной страницы путь '/' или '/products'
      return this.$route?.path === '/' || this.$route?.path === '/products';
    },
    
    // Если ProductsView - это ваша главная страница
    isOnProductsView() {
      // Проверяем имя маршрута или путь
      return this.$route?.name === 'ProductsView' || 
             this.$route?.path === '/products' ||
             this.$route?.path === '/';
    },
    
    allCartItems() {
      const savedCart = localStorage.getItem('shoppingCart');
      if (!savedCart) return [];
      
      const cartItems = JSON.parse(savedCart);
      
      return cartItems.map(item => {
        const restaurant = this.restaurants.find(r => r.id === item.restaurantId);
        return {
          ...item,
          restaurantName: restaurant ? restaurant.title : `Ресторан #${item.restaurantId}`
        };
      });
    },




    allCartItems() {
      const savedCart = localStorage.getItem('shoppingCart');
      if (!savedCart) return [];
      
      const cartItems = JSON.parse(savedCart);
      
      return cartItems.map(item => {
        const restaurant = this.restaurants.find(r => r.id === item.restaurantId);
        return {
          ...item,
          restaurantName: restaurant ? restaurant.title : `Ресторан #${item.restaurantId}`
        };
      });
    },
    
    groupedByRestaurant() {
      const groups = {};
      
      this.allCartItems.forEach(item => {
        if (!groups[item.restaurantId]) {
          const restaurant = this.restaurants.find(r => r.id === item.restaurantId);
          groups[item.restaurantId] = {
            id: item.restaurantId,
            name: restaurant ? restaurant.title : `Ресторан #${item.restaurantId}`,
            items: [],
            total: 0
          };
        }
        
        groups[item.restaurantId].items.push(item);
        groups[item.restaurantId].total += item.price * item.quantity;
      });
      
      return Object.values(groups).sort((a, b) => a.name.localeCompare(b.name));
    },
    
    totalCartPrice() {
      return this.allCartItems.reduce((total, item) => {
        return total + (item.price * item.quantity);
      }, 0);
    }
  },
  methods: {
    toggleCartPopup() {
      this.showCartPopup = !this.showCartPopup;
    },
    
    closeCartPopup() {
      this.showCartPopup = false;
    },
    
    goToCartPage() {
      if (this.$router) {
        this.$router.push('/check');
      }
      this.closeCartPopup();
    },
    
    // Переход к странице ресторана
    goToRestaurant(restaurantId) {
  if (this.$router) {
    this.$router.push(`/product/${restaurantId}`);
  } else {
    window.location.href = `/product/${restaurantId}`;
  }
  this.closeCartPopup();
}
  },
  mounted() {
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.showCartPopup) {
        this.closeCartPopup();
      }
    });
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this.handleEscape);
  }
}
</script>


<style scoped>


@import "../assets/styles/header.css";


.cart-popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: flex-end;
  align-items: flex-start;
  z-index: 1000;
  padding-top: 80px;
}

.cart-popup {
  background-color: white;
  border-radius: 8px;
  width: 420px; /* Немного шире для кнопки */
  max-height: 600px;
  margin-right: 20px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
}

.cart-popup-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
}

.cart-popup-header h3 {
  margin: 0;
  font-size: 18px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.close-btn:hover {
  background-color: #f5f5f5;
}

.cart-popup-content {
  flex: 1;
  overflow-y: auto;
  padding: 0;
}

.empty-cart-popup {
  padding: 40px 20px;
  text-align: center;
  color: #666;
}

.cart-items-popup {
  padding: 0;
}

/* Стили для группы ресторана */
.restaurant-group {
  margin-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.restaurant-group:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.restaurant-header {
  background-color: #f9f9f9;
  padding: 10px 15px;
  border-bottom: 1px solid #eee;
}

.restaurant-header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.restaurant-header h4 {
  margin: 0;
  font-size: 16px;
  color: #333;
  font-weight: 600;
}

/* Кнопка перехода к ресторану */
.go-to-restaurant-btn {
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  width: 30px;
  height: 30px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  padding: 0;
  line-height: 1;
}

.go-to-restaurant-btn:hover {
  background-color: #45a049;
  transform: translateX(2px);
}

.go-to-restaurant-btn:active {
  transform: scale(0.95);
}

.restaurant-items {
  padding: 0 15px;
}

.cart-item-popup {
  padding: 10px 0;
  border-bottom: 1px solid #f5f5f5;
}

.cart-item-popup:last-child {
  border-bottom: none;
}

.cart-item-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.item-name {
  font-weight: 500;
  color: #333;
  flex: 1;
  padding-right: 10px;
}

.item-details {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 3px;
  min-width: 120px;
}

.item-details span {
  font-size: 14px;
  color: #666;
}

.item-total {
  font-weight: 600;
  color: #333;
  font-size: 15px;
}

.restaurant-total {
  background-color: #f0f9ff;
  padding: 8px 15px;
  font-size: 14px;
  color: #333;
  font-weight: 500;
  border-top: 1px solid #e3f2fd;
  text-align: right;
}

.cart-popup-summary {
  padding: 15px;
  background-color: #f9f9f9;
  border-top: 2px solid #ddd;
  text-align: center;
  margin-top: 10px;
}

.cart-popup-summary p {
  margin: 0 0 15px 0;
  font-size: 18px;
  color: #333;
  font-weight: bold;
}

.view-full-cart-btn {
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 10px 20px;
  cursor: pointer;
  font-size: 14px;
  width: 100%;
  transition: background-color 0.2s;
}

.view-full-cart-btn:hover {
  background-color: #45a049;
}




</style>
