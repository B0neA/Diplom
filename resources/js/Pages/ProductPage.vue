<template> 
  <div class="container">
    <div class="ad">
      <header>
        <div v-if="product">
          <h2>{{ product.title }}</h2>
          <p> ★ {{ product.rating }}</p>
        </div>
      </header>   
              
      <main class="products">
        <!-- Карточки продуктов -->
        <div v-for="item in restaurantProducts" :key="item.id" class="product-card">
          <div class="product-info">
            <h3>{{ item.name }}</h3>
            <p class="category">{{ item.category }}</p>
            <p class="description">{{ item.description }}</p>
          </div>
          <div class="product-price">
            <div class="price-cont">
              <span class="price">{{ item.price }} ₽</span>

              <div class="quantity-control" v-if="getCartQuantity(item.id) > 0">
                <button @click="decreaseCartQuantity(item)" class="quantity-btn minus">-</button>
                <span class="quantity-display">{{ getCartQuantity(item.id) }}</span>
                <button @click="addToCart(item)" class="quantity-btn plus">+</button>
              </div>
              <button 
                v-else 
                @click="addToCart(item)" 
                class="add-to-cart-btn"
              >
                +
              </button>
            </div>
          </div>
        </div>
      </main>
    </div>
    
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
</template>

<script>
import CartComponent from '../../Components/CartComponent.vue';

export default {
  name: 'ProductPage',
  components: { CartComponent },
  props: ['id'],
  data() {
    return {
      allProducts: [
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
      ],
      productsDatabase: [
        // KFC (1)
        { id: 1, restaurantId: 1, name: 'Чизбургер', category: 'Бургеры', description: 'Сочный бургер с сыром', price: 199 },
        { id: 2, restaurantId: 1, name: 'Картошка фри', category: 'Гарниры', description: 'Хрустящая картошка фри', price: 129 },
        { id: 3, restaurantId: 1, name: 'Наггетсы', category: 'Закуски', description: 'Куриные наггетсы 10 шт', price: 189 },
        { id: 4, restaurantId: 1, name: 'Кола', category: 'Напитки', description: 'Газированный напиток 0.5л', price: 99 },
        { id: 5, restaurantId: 1, name: 'Твистер', category: 'Бургеры', description: 'Острый бургер с курицей', price: 249 },
        { id: 6, restaurantId: 1, name: 'Стрипсы', category: 'Закуски', description: 'Куриные стрипсы 6 шт', price: 229 },
        { id: 7, restaurantId: 1, name: 'Пивной сет', category: 'Комбо', description: 'Наггетсы + картошка + соус', price: 349 },
        { id: 8, restaurantId: 1, name: 'Айс Ти', category: 'Напитки', description: 'Холодный чай 0.5л', price: 119 },
        { id: 9, restaurantId: 1, name: 'Чикенбургер', category: 'Бургеры', description: 'Бургер с куриной котлетой', price: 159 },
        { id: 10, restaurantId: 1, name: 'Пирожок', category: 'Выпечка', description: 'Пирожок с вишней', price: 89 },
        
        // Велопицца (2)
        { id: 11, restaurantId: 2, name: 'Маргарита', category: 'Пиццы', description: 'Классическая пицца', price: 450 },
        { id: 12, restaurantId: 2, name: 'Пепперони', category: 'Пиццы', description: 'Острая пицца с колбасками', price: 520 },
        { id: 13, restaurantId: 2, name: 'Цезарь', category: 'Салаты', description: 'Салат с курицей', price: 320 },
        { id: 14, restaurantId: 2, name: 'Четыре сыра', category: 'Пиццы', description: 'Сырная пицца', price: 580 },
        { id: 15, restaurantId: 2, name: 'Гавайская', category: 'Пиццы', description: 'С курицей и ананасами', price: 490 },
        { id: 16, restaurantId: 2, name: 'Карбонара', category: 'Паста', description: 'Паста с беконом', price: 380 },
        { id: 17, restaurantId: 2, name: 'Греческий', category: 'Салаты', description: 'Салат с оливками и сыром', price: 290 },
        { id: 18, restaurantId: 2, name: 'Мясная', category: 'Пиццы', description: 'Пицца с тремя видами мяса', price: 620 },
        { id: 19, restaurantId: 2, name: 'Браун', category: 'Десерты', description: 'Шоколадный брауни', price: 220 },
        { id: 20, restaurantId: 2, name: 'Лимонад', category: 'Напитки', description: 'Домашний лимонад', price: 180 },
        
        // Вкусно и точка (3)
        { id: 21, restaurantId: 3, name: 'Биг Спешиал', category: 'Бургеры', description: 'Большой бургер', price: 250 },
        { id: 22, restaurantId: 3, name: 'Картофель по-деревенски', category: 'Гарниры', description: 'Запеченный картофель', price: 150 },
        { id: 23, restaurantId: 3, name: 'Чизбургер', category: 'Бургеры', description: 'Бургер с двумя котлетами', price: 210 },
        { id: 24, restaurantId: 3, name: 'Наггетсы', category: 'Закуски', description: 'Куриные наггетсы 8 шт', price: 170 },
        { id: 25, restaurantId: 3, name: 'Картошка фри', category: 'Гарниры', description: 'Стандартная порция', price: 120 },
        { id: 26, restaurantId: 3, name: 'Мороженое', category: 'Десерты', description: 'Ванильное мороженое', price: 90 },
        { id: 27, restaurantId: 3, name: 'Шейк', category: 'Напитки', description: 'Клубничный молочный коктейль', price: 190 },
        { id: 28, restaurantId: 3, name: 'Спайси бургер', category: 'Бургеры', description: 'Острый бургер', price: 230 },
        { id: 29, restaurantId: 3, name: 'Куриные крылышки', category: 'Закуски', description: 'Крылышки в соусе барбекю', price: 280 },
        { id: 30, restaurantId: 3, name: 'Яблочный пирог', category: 'Десерты', description: 'Теплый яблочный пирог', price: 130 },
        
        // На лаваше (4)
        { id: 31, restaurantId: 4, name: 'Шаурма классическая', category: 'Шаурма', description: 'С курицей и овощами', price: 280 },
        { id: 32, restaurantId: 4, name: 'Лаваш с говядиной', category: 'Лаваши', description: 'Сочный лаваш', price: 320 },
        { id: 33, restaurantId: 4, name: 'Шаурма острая', category: 'Шаурма', description: 'С острым соусом', price: 290 },
        { id: 34, restaurantId: 4, name: 'Фалафель', category: 'Вегетарианское', description: 'Шаурма с фалафелем', price: 250 },
        { id: 35, restaurantId: 4, name: 'Хот-дог', category: 'Снэки', description: 'С сосисками и овощами', price: 180 },
        { id: 36, restaurantId: 4, name: 'Картошка фри', category: 'Гарниры', description: 'С сырным соусом', price: 140 },
        { id: 37, restaurantId: 4, name: 'Самса', category: 'Выпечка', description: 'С мясом 2 шт', price: 160 },
        { id: 38, restaurantId: 4, name: 'Кола', category: 'Напитки', description: '0.33л', price: 110 },
        { id: 39, restaurantId: 4, name: 'Чебурек', category: 'Выпечка', description: 'Жареный чебурек', price: 120 },
        { id: 40, restaurantId: 4, name: 'Сырные палочки', category: 'Закуски', description: 'С соусом 6 шт', price: 190 },
        
        // Pizzaiolo (5)
        { id: 41, restaurantId: 5, name: 'Четыре сыра', category: 'Пиццы', description: 'Сырная пицца', price: 580 },
        { id: 42, restaurantId: 5, name: 'Морепродукты', category: 'Пиццы', description: 'Пицца с морепродуктами', price: 650 },
        { id: 43, restaurantId: 5, name: 'Капрезе', category: 'Салаты', description: 'Салат с моцареллой', price: 380 },
        { id: 44, restaurantId: 5, name: 'Кальцоне', category: 'Пиццы', description: 'Закрытая пицца', price: 420 },
        { id: 45, restaurantId: 5, name: 'Тирамису', category: 'Десерты', description: 'Итальянский десерт', price: 280 },
        { id: 46, restaurantId: 5, name: 'Лазанья', category: 'Паста', description: 'С мясным соусом', price: 450 },
        { id: 47, restaurantId: 5, name: 'Прошутто', category: 'Пиццы', description: 'С ветчиной и грибами', price: 520 },
        { id: 48, restaurantId: 5, name: 'Моцарелла', category: 'Закуски', description: 'С помидорами и базиликом', price: 320 },
        { id: 49, restaurantId: 5, name: 'Равиоли', category: 'Паста', description: 'С творогом и шпинатом', price: 390 },
        { id: 50, restaurantId: 5, name: 'Лимончелло', category: 'Напитки', description: 'Итальянский ликер', price: 350 },
        
        // Burger King (6)
        { id: 51, restaurantId: 6, name: 'Воппер', category: 'Бургеры', description: 'Легендарный бургер', price: 299 },
        { id: 52, restaurantId: 6, name: 'Чизбургер', category: 'Бургеры', description: 'С двумя котлетами', price: 189 },
        { id: 53, restaurantId: 6, name: 'Картошка фри', category: 'Гарниры', description: 'Большая порция', price: 149 },
        { id: 54, restaurantId: 6, name: 'Кинг фри', category: 'Закуски', description: 'Картошка с сыром', price: 179 },
        { id: 55, restaurantId: 6, name: 'Чикенбургер', category: 'Бургеры', description: 'С куриной котлетой', price: 169 },
        { id: 56, restaurantId: 6, name: 'Шейк', category: 'Напитки', description: 'Шоколадный коктейль', price: 199 },
        { id: 57, restaurantId: 6, name: 'Наггетсы', category: 'Закуски', description: '20 шт с соусом', price: 249 },
        { id: 58, restaurantId: 6, name: 'Сандвич', category: 'Сэндвичи', description: 'С курицей и беконом', price: 229 },
        { id: 59, restaurantId: 6, name: 'Лук фри', category: 'Закуски', description: 'Хрустящий жареный лук', price: 159 },
        { id: 60, restaurantId: 6, name: 'Мороженое', category: 'Десерты', description: 'Вафельный рожок', price: 99 },
        
        // Subway (7)
        { id: 61, restaurantId: 7, name: 'Итальянский BMT', category: 'Сэндвичи', description: 'С колбасами', price: 320 },
        { id: 62, restaurantId: 7, name: 'Куриный терияки', category: 'Сэндвичи', description: 'С курицей терияки', price: 290 },
        { id: 63, restaurantId: 7, name: 'Вегетарианский', category: 'Сэндвичи', description: 'С овощами', price: 250 },
        { id: 64, restaurantId: 7, name: 'Тунец', category: 'Сэндвичи', description: 'С тунцом и сыром', price: 310 },
        { id: 65, restaurantId: 7, name: 'Салат Цезарь', category: 'Салаты', description: 'В коробочке', price: 280 },
        { id: 66, restaurantId: 7, name: 'Пепперони', category: 'Сэндвичи', description: 'С пепперони', price: 300 },
        { id: 67, restaurantId: 7, name: 'Печенье', category: 'Десерты', description: 'Шоколадное печенье', price: 90 },
        { id: 68, restaurantId: 7, name: 'Напиток', category: 'Напитки', description: 'Газировка 0.5л', price: 120 },
        { id: 69, restaurantId: 7, name: 'Сырный хлеб', category: 'Выпечка', description: 'С чесночным соусом', price: 150 },
        { id: 70, restaurantId: 7, name: 'Куриный суп', category: 'Супы', description: 'С лапшой', price: 200 },
        
        // Starbucks (8)
        { id: 71, restaurantId: 8, name: 'Латте', category: 'Кофе', description: 'Классический латте', price: 290 },
        { id: 72, restaurantId: 8, name: 'Капучино', category: 'Кофе', description: 'С пенкой', price: 280 },
        { id: 73, restaurantId: 8, name: 'Фраппучино', category: 'Кофе', description: 'Карамельный холодный', price: 350 },
        { id: 74, restaurantId: 8, name: 'Американо', category: 'Кофе', description: 'Черный кофе', price: 220 },
        { id: 75, restaurantId: 8, name: 'Мокачино', category: 'Кофе', description: 'С шоколадом', price: 320 },
        { id: 76, restaurantId: 8, name: 'Чай матча', category: 'Чай', description: 'Зеленый чай матча', price: 310 },
        { id: 77, restaurantId: 8, name: 'Круассан', category: 'Выпечка', description: 'С шоколадом', price: 180 },
        { id: 78, restaurantId: 8, name: 'Чизкейк', category: 'Десерты', description: 'Нью-Йоркский', price: 320 },
        { id: 79, restaurantId: 8, name: 'Маффин', category: 'Выпечка', description: 'С черникой', price: 190 },
        { id: 80, restaurantId: 8, name: 'Гляссе', category: 'Кофе', description: 'Кофе с мороженым', price: 340 },
        
        
        
        // Теремок (10)
        { id: 91, restaurantId: 10, name: 'Блины с мясом', category: 'Блины', description: 'С говяжьим фаршем', price: 220 },
        { id: 92, restaurantId: 10, name: 'Блины с творогом', category: 'Блины', description: 'С творогом и изюмом', price: 190 },
        { id: 93, restaurantId: 10, name: 'Борщ', category: 'Супы', description: 'С говядиной и сметаной', price: 180 },
        { id: 94, restaurantId: 10, name: 'Сырники', category: 'Десерты', description: 'Со сметаной 3 шт', price: 160 },
        { id: 95, restaurantId: 10, name: 'Пельмени', category: 'Основные', description: 'Свиные 15 шт', price: 250 },
        { id: 96, restaurantId: 10, name: 'Гречка с грибами', category: 'Основные', description: 'С жареными грибами', price: 210 },
        { id: 97, restaurantId: 10, name: 'Компот', category: 'Напитки', description: 'Ягодный компот', price: 90 },
        { id: 98, restaurantId: 10, name: 'Вареники', category: 'Основные', description: 'С картошкой 10 шт', price: 230 },
        { id: 99, restaurantId: 10, name: 'Оливье', category: 'Салаты', description: 'Классический салат', price: 170 },
        { id: 100, restaurantId: 10, name: 'Квас', category: 'Напитки', description: 'Домашний квас', price: 120 },
        
        // Шаурма №1 (11)
        { id: 101, restaurantId: 11, name: 'Шаурма классика', category: 'Шаурма', description: 'С курицей', price: 270 },
        { id: 102, restaurantId: 11, name: 'Шаурма люкс', category: 'Шаурма', description: 'С говядиной', price: 320 },
        { id: 103, restaurantId: 11, name: 'Лаваш', category: 'Лаваши', description: 'С овощами и соусом', price: 250 },
        { id: 104, restaurantId: 11, name: 'Фалафель', category: 'Вегетарианское', description: 'С нутовыми котлетами', price: 230 },
        { id: 105, restaurantId: 11, name: 'Картошка фри', category: 'Гарниры', description: 'С сыром', price: 150 },
        { id: 106, restaurantId: 11, name: 'Самса', category: 'Выпечка', description: 'С курицей', price: 140 },
        { id: 107, restaurantId: 11, name: 'Хычины', category: 'Выпечка', description: 'С сыром 2 шт', price: 180 },
        { id: 108, restaurantId: 11, name: 'Айран', category: 'Напитки', description: 'Кисломолочный напиток', price: 110 },
        { id: 109, restaurantId: 11, name: 'Чебурек', category: 'Выпечка', description: 'С мясом', price: 130 },
        { id: 110, restaurantId: 11, name: 'Соусный сет', category: 'Соусы', description: '3 вида соусов', price: 100 },
        
        // Dominos Pizza (12)
        { id: 111, restaurantId: 12, name: 'Пепперони', category: 'Пиццы', description: 'С двойной порцией', price: 550 },
        { id: 112, restaurantId: 12, name: 'Гавайская', category: 'Пиццы', description: 'С ананасами', price: 520 },
        { id: 113, restaurantId: 12, name: 'Маргарита', category: 'Пиццы', description: 'Классическая', price: 480 },
        { id: 114, restaurantId: 12, name: 'Барбекю', category: 'Пиццы', description: 'С соусом барбекю', price: 590 },
        { id: 115, restaurantId: 12, name: 'Четыре сыра', category: 'Пиццы', description: 'Сырное ассорти', price: 570 },
        { id: 116, restaurantId: 12, name: 'Картофель из печи', category: 'Закуски', description: 'С чесночным соусом', price: 220 },
        { id: 117, restaurantId: 12, name: 'Куриные крылья', category: 'Закуски', description: 'В медовом соусе', price: 380 },
        { id: 118, restaurantId: 12, name: 'Салат Цезарь', category: 'Салаты', description: 'С курицей', price: 320 },
        { id: 119, restaurantId: 12, name: 'Шоколадный ролл', category: 'Десерты', description: 'С корицей', price: 250 },
        { id: 120, restaurantId: 12, name: 'Лимонад', category: 'Напитки', description: 'Домашний', price: 180 },
        
        // Суши Wok (13)
        { id: 121, restaurantId: 13, name: 'Филадельфия', category: 'Суши', description: 'С лососем и авокадо', price: 420 },
        { id: 122, restaurantId: 13, name: 'Калифорния', category: 'Суши', description: 'С крабом и икрой', price: 380 },
        { id: 123, restaurantId: 13, name: 'Темпура', category: 'Роллы', description: 'В кляре', price: 350 },
        { id: 124, restaurantId: 13, name: 'Вок с курицей', category: 'Вок', description: 'С овощами', price: 320 },
        { id: 125, restaurantId: 13, name: 'Рис с креветками', category: 'Основные', description: 'С соевым соусом', price: 290 },
        { id: 126, restaurantId: 13, name: 'Мисо суп', category: 'Супы', description: 'С тофу', price: 180 },
        { id: 127, restaurantId: 13, name: 'Сашими', category: 'Суши', description: 'Лосось 8 шт', price: 450 },
        { id: 128, restaurantId: 13, name: 'Гунканы', category: 'Суши', description: 'С тунцом 4 шт', price: 280 },
        { id: 129, restaurantId: 13, name: 'Зеленый чай', category: 'Напитки', description: 'Японский', price: 150 },
        { id: 130, restaurantId: 13, name: 'Мохито', category: 'Напитки', description: 'Безалкогольный', price: 200 },
        
        // Kebab House (14)
        { id: 131, restaurantId: 14, name: 'Донер кебаб', category: 'Кебабы', description: 'С курицей', price: 310 },
        { id: 132, restaurantId: 14, name: 'Шашлык', category: 'Мясо', description: 'Свиной 5 шт', price: 420 },
        { id: 133, restaurantId: 14, name: 'Лахмаджун', category: 'Выпечка', description: 'Турецкая пицца', price: 230 },
        { id: 134, restaurantId: 14, name: 'Пиде', category: 'Выпечка', description: 'Турецкая лепешка', price: 270 },
        { id: 135, restaurantId: 14, name: 'Хумус', category: 'Закуски', description: 'С питой', price: 190 },
        { id: 136, restaurantId: 14, name: 'Баклава', category: 'Десерты', description: 'С орехами', price: 210 },
        { id: 137, restaurantId: 14, name: 'Айран', category: 'Напитки', description: 'Освежающий', price: 120 },
        { id: 138, restaurantId: 14, name: 'Аджапсандал', category: 'Закуски', description: 'Овощная закуска', price: 180 },
        { id: 139, restaurantId: 14, name: 'Кефте', category: 'Мясо', description: 'Мясные тефтели', price: 350 },
        { id: 140, restaurantId: 14, name: 'Чай', category: 'Напитки', description: 'Турецкий', price: 100 },
        
        // Кофемания (15)
        { id: 141, restaurantId: 15, name: 'Раф', category: 'Кофе', description: 'С ванильным сиропом', price: 320 },
        { id: 142, restaurantId: 15, name: 'Флэт Уайт', category: 'Кофе', description: 'С молоком', price: 280 },
        { id: 143, restaurantId: 15, name: 'Эспрессо', category: 'Кофе', description: 'Двойной', price: 200 },
        { id: 144, restaurantId: 15, name: 'Какао', category: 'Напитки', description: 'С маршмеллоу', price: 250 },
        { id: 145, restaurantId: 15, name: 'Тирамису', category: 'Десерты', description: 'Классический', price: 350 },
        { id: 146, restaurantId: 15, name: 'Брауни', category: 'Десерты', description: 'С мороженым', price: 290 },
        { id: 147, restaurantId: 15, name: 'Багет', category: 'Сэндвичи', description: 'С ветчиной и сыром', price: 240 },
        { id: 148, restaurantId: 15, name: 'Салат', category: 'Салаты', description: 'С авокадо и креветками', price: 420 },
        { id: 149, restaurantId: 15, name: 'Матча латте', category: 'Чай', description: 'С молоком', price: 330 },
        { id: 150, restaurantId: 15, name: 'Мороженое', category: 'Десерты', description: 'Фисташковое', price: 220 },
        
        // Чайхона №1 (16)
        { id: 151, restaurantId: 16, name: 'Плов', category: 'Основные', description: 'Узбекский', price: 380 },
        { id: 152, restaurantId: 16, name: 'Шашлык', category: 'Мясо', description: 'Бараньи ребрышки', price: 450 },
        { id: 153, restaurantId: 16, name: 'Манты', category: 'Основные', description: 'С бараниной 6 шт', price: 320 },
        { id: 154, restaurantId: 16, name: 'Самса', category: 'Выпечка', description: 'С бараниной', price: 180 },
        { id: 155, restaurantId: 16, name: 'Лагман', category: 'Супы', description: 'С говядиной', price: 290 },
        { id: 156, restaurantId: 16, name: 'Чай', category: 'Напитки', description: 'Зеленый с травами', price: 150 },
        { id: 157, restaurantId: 16, name: 'Шурпа', category: 'Супы', description: 'Густой суп', price: 270 },
        { id: 158, restaurantId: 16, name: 'Хаш', category: 'Супы', description: 'Говяжий', price: 350 },
        { id: 159, restaurantId: 16, name: 'Шакараб', category: 'Салаты', description: 'Овощной салат', price: 210 },
        { id: 160, restaurantId: 16, name: 'Халва', category: 'Десерты', description: 'Восточная сладость', price: 190 },
        
        // Пицца Темпо (17)
        { id: 161, restaurantId: 17, name: 'Мясная', category: 'Пиццы', description: 'С тремя видами мяса', price: 520 },
        { id: 162, restaurantId: 17, name: 'Вегетарианская', category: 'Пиццы', description: 'С овощами', price: 450 },
        { id: 163, restaurantId: 17, name: 'Диабло', category: 'Пиццы', description: 'Острая', price: 480 },
        { id: 164, restaurantId: 17, name: 'Карбонара', category: 'Паста', description: 'С беконом', price: 380 },
        { id: 165, restaurantId: 17, name: 'Цезарь', category: 'Салаты', description: 'С курицей', price: 290 },
        { id: 166, restaurantId: 17, name: 'Чесночные гренки', category: 'Закуски', description: 'С сыром', price: 180 },
        { id: 167, restaurantId: 17, name: 'Моцарелла', category: 'Закуски', description: 'С помидорами', price: 250 },
        { id: 168, restaurantId: 17, name: 'Тирамису', category: 'Десерты', description: 'Итальянский', price: 280 },
        { id: 169, restaurantId: 17, name: 'Лимонад', category: 'Напитки', description: 'Мятный', price: 170 },
        { id: 170, restaurantId: 17, name: 'Кальцоне', category: 'Пиццы', description: 'Закрытая', price: 430 },
        
        // Вареничная (18)
        { id: 171, restaurantId: 18, name: 'Вареники с картошкой', category: 'Вареники', description: 'Со шкварками', price: 240 },
        { id: 172, restaurantId: 18, name: 'Вареники с вишней', category: 'Вареники', description: 'С сахаром', price: 210 },
        { id: 173, restaurantId: 18, name: 'Вареники с творогом', category: 'Вареники', description: 'Со сметаной', price: 220 },
        { id: 174, restaurantId: 18, name: 'Борщ', category: 'Супы', description: 'Украинский', price: 190 },
        { id: 175, restaurantId: 18, name: 'Голубцы', category: 'Основные', description: 'С мясом и рисом', price: 270 },
        { id: 176, restaurantId: 18, name: 'Сало', category: 'Закуски', description: 'С чесноком', price: 180 },
        { id: 177, restaurantId: 18, name: 'Квас', category: 'Напитки', description: 'Домашний', price: 120 },
        { id: 178, restaurantId: 18, name: 'Сметана', category: 'Добавки', description: 'Домашняя', price: 80 },
        { id: 179, restaurantId: 18, name: 'Компот', category: 'Напитки', description: 'Из сухофруктов', price: 100 },
        { id: 180, restaurantId: 18, name: 'Пампушки', category: 'Выпечка', description: 'С чесноком', price: 150 },
        
        // Шоколадница (19)
        { id: 181, restaurantId: 19, name: 'Горячий шоколад', category: 'Напитки', description: 'С зефиром', price: 280 },
        { id: 182, restaurantId: 19, name: 'Кофе по-венски', category: 'Кофе', description: 'Со сливками', price: 310 },
        { id: 183, restaurantId: 19, name: 'Эклер', category: 'Десерты', description: 'С заварным кремом', price: 190 },
        { id: 184, restaurantId: 19, name: 'Наполеон', category: 'Десерты', description: 'Слоеный торт', price: 230 },
        { id: 185, restaurantId: 19, name: 'Сырники', category: 'Завтраки', description: 'Со сметаной', price: 270 },
        { id: 186, restaurantId: 19, name: 'Омлет', category: 'Завтраки', description: 'С ветчиной и сыром', price: 320 },
        { id: 187, restaurantId: 19, name: 'Салат', category: 'Салаты', description: 'С тунцом', price: 350 },
        { id: 188, restaurantId: 19, name: 'Молочный коктейль', category: 'Напитки', description: 'Клубничный', price: 290 },
        { id: 189, restaurantId: 19, name: 'Блины', category: 'Десерты', description: 'С кленовым сиропом', price: 250 },
        { id: 190, restaurantId: 19, name: 'Чизкейк', category: 'Десерты', description: 'Нью-Йоркский', price: 340 },
        
        // Якитория (20)
        { id: 191, restaurantId: 20, name: 'Якитори', category: 'Гриль', description: 'Куриные шашлычки', price: 320 },
        { id: 192, restaurantId: 20, name: 'Сашими', category: 'Суши', description: 'Лосось 10 шт', price: 480 },
        { id: 193, restaurantId: 20, name: 'Ролл Филадельфия', category: 'Роллы', description: 'С лососем', price: 420 },
        { id: 194, restaurantId: 20, name: 'Темпура', category: 'Закуски', description: 'Креветки в кляре', price: 380 },
        { id: 195, restaurantId: 20, name: 'Мисо суп', category: 'Супы', description: 'С водорослями', price: 200 },
        { id: 196, restaurantId: 20, name: 'Рис', category: 'Гарниры', description: 'С овощами', price: 180 },
        { id: 197, restaurantId: 20, name: 'Эдамаме', category: 'Закуски', description: 'Соленые соевые бобы', price: 220 },
        { id: 198, restaurantId: 20, name: 'Саке', category: 'Напитки', description: 'Японское', price: 350 },
        { id: 199, restaurantId: 20, name: 'Рамэн', category: 'Супы', description: 'С свининой', price: 410 },
        { id: 200, restaurantId: 20, name: 'Моти', category: 'Десерты', description: 'С мороженым', price: 280 }
      ],
      cartItems: []
    }
  },
  computed: {
    product() {
      return this.allProducts.find(p => p.id === Number(this.id))
    },
    restaurantProducts() {
      if (!this.product) return []
      return this.productsDatabase.filter(p => p.restaurantId === Number(this.id))
    }
  },
  methods: {
  // Получить количество товара в корзине
  getCartQuantity(productId) {
    const item = this.cartItems.find(item => item.id === productId);
    return item ? item.quantity : 0;
  },
  
  // Добавить товар в корзину
  addToCart(product) {
    const existingItem = this.cartItems.find(item => item.id === product.id);
    
    if (existingItem) {
      existingItem.quantity++;
    } else {
      this.cartItems.push({
        ...product,
        quantity: 1
      });
    }
  },
  
  // Уменьшить количество товара в корзине (используется на кнопке в карточке товара)
  decreaseCartQuantity(product) {
    const existingItem = this.cartItems.find(item => item.id === product.id);
    
    if (existingItem) {
      if (existingItem.quantity > 1) {
        existingItem.quantity--;
      } else {
        // Если количество становится 0, удаляем товар
        this.removeItem(existingItem); // Исправлено здесь
      }
    }
  },
  
  // Увеличить количество товара (для корзины)
  increaseQuantity(item) {
    item.quantity++;
  },
  
  // Уменьшить количество товара (для корзины)
  decreaseQuantity(item) {
    if (item.quantity > 1) {
      item.quantity--;
    } else {
      this.removeItem(item);
    }
  },
  
  // Удалить товар из корзины
  removeItem(item) {
    const index = this.cartItems.findIndex(cartItem => cartItem.id === item.id);
    if (index !== -1) {
      this.cartItems.splice(index, 1);
    }
  },
  
  // Очистить всю корзину
  clearCart() {
      this.cartItems = [];
    },
    
    // Очистка только товаров текущего ресторана
    clearRestaurantCart(restaurantId) {
      if (confirm('Очистить корзину этого ресторана?')) {
        this.cartItems = this.cartItems.filter(item => 
          item.restaurantId !== restaurantId
        );
      }
    },
  
  // Оформить заказ
  checkout() {
    if (this.cartItems.length === 0) {
      alert('Корзина пуста!');
      return;
    }
    
    alert(`Заказ оформлен! Сумма: ${this.totalPrice} ₽`);
    this.clearCart();
  },
  
  // Вычисляем общую сумму
  get totalPrice() {
    if (!this.cartItems || !Array.isArray(this.cartItems)) {
    return 0;
  }
  return this.cartItems.reduce((total, item) => {
    return total + (item.price * item.quantity);
  }, 0);
  }
},
  mounted() {
    console.log('ProductPage mounted, id:', this.id)
    console.log('Found product:', this.product)
    console.log('Restaurant products:', this.restaurantProducts)
    
    const savedCart = localStorage.getItem('shoppingCart');
    if (savedCart) {
      this.cartItems = JSON.parse(savedCart);
    }
  },
  watch: {
    cartItems: {
      handler(newCart) {
        localStorage.setItem('shoppingCart', JSON.stringify(newCart));
      },
      deep: true
    }
  }
}
</script>

<style scoped>
.container {
  display: flex;
  gap: 20px;
  justify-content: space-between;
  margin: 20px 50px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

header {
  background-color: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  min-width: 920px;
  border: 1px solid #e0e0e0;
}

header h2 {
  margin: 0 0 10px 0;
  font-size: 24px;
  font-weight: 600;
  color: #333;
}

header p {
  display: inline-flex;
  padding: 8px 16px;
  border: 1px solid #ff9800;
  border-radius: 20px;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 500;
  color: #ff9800;
  background-color: #fff8e1;
}

.ad {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.products {
  background-color: #f8f9fa;
  border-radius: 12px;
  height: 100%;
  overflow-y: auto;
  padding: 20px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
  border: 1px solid #e0e0e0;
}

.product-card {
  background-color: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  max-height: 160px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid #e0e0e0;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.product-info {
  flex: 1;
}

.product-info h3 {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 18px;
  font-weight: 600;
}

.category {
  color: #666;
  font-size: 14px;
  margin: 0 0 8px 0;
  font-weight: 500;
}

.description {
  color: #777;
  font-size: 14px;
  margin: 0;
  line-height: 1.4;
}

.product-price {
  display: flex;
  align-items: flex-end;
  gap: 15px;
}

.price-cont {
  display: flex;
  align-items: center;
  gap: 15px;
}

.price {
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.add-to-cart-btn {
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  font-size: 24px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(76, 175, 80, 0.2);
}

.add-to-cart-btn:hover {
  background-color: #45a049;
  transform: scale(1.05);
}

.add-to-cart-btn:active {
  transform: scale(0.95);
}

.quantity-control {
  display: flex;
  align-items: center;
  gap: 8px;
}

.quantity-btn {
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  font-size: 20px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.quantity-btn:hover {
  background-color: #45a049;
  transform: scale(1.05);
}

.quantity-btn:active {
  transform: scale(0.95);
}

.quantity-display {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  min-width: 30px;
  text-align: center;
}
</style>