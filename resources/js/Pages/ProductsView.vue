<script setup>
import HeaderComponent from '../../Components/HeaderComponent.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';

// Получаем данные из пропсов от Laravel
const page = usePage();
const props = computed(() => page.props);

// Реактивные данные
const view = ref('grid');
const searchQuery = ref('');
const selectedCategory = ref('');
const sortBy = ref('rating');
const sortOrder = ref('desc');

// Данные из БД (передаются через контроллер)
const products = computed(() => props.value.products?.data || []);
const categories = computed(() => props.value.categories || []);
const pagination = computed(() => props.value.products);

// Дополнительно: если нужна realtime синхронизация с Supabase
const isRealtimeEnabled = ref(false);

const isGrid = () => view.value === 'grid';

const goToProduct = (id) => {
    router.visit(`/product/${id}`);
};

const applyFilters = () => {
    router.visit('/', {
        method: 'get',
        data: {
            search: searchQuery.value,
            category: selectedCategory.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
        },
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = '';
    sortBy.value = 'rating';
    sortOrder.value = 'desc';
    applyFilters();
};

const changePage = (url) => {
    if (url) {
        router.visit(url, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

// Дебаунс для поиска
let debounceTimeout;
watch(searchQuery, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

watch([selectedCategory, sortBy, sortOrder], () => {
    applyFilters();
});

// Опционально: Realtime подписка на изменения в Supabase
const enableRealtime = () => {
    if (!window.supabase) return;
    
    const supabase = window.supabase.createClient(
        import.meta.env.VITE_SUPABASE_URL,
        import.meta.env.VITE_SUPABASE_ANON_KEY
    );
    
    supabase
        .channel('products-changes')
        .on('postgres_changes', 
            { event: '*', schema: 'public', table: 'products' },
            (payload) => {
                console.log('Изменение в БД:', payload);
                // Обновляем данные
                applyFilters();
            }
        )
        .subscribe();
    
    isRealtimeEnabled.value = true;
};

// Включаем realtime только если нужно
onMounted(() => {
    if (import.meta.env.VITE_ENABLE_REALTIME === 'true') {
        enableRealtime();
    }
});
</script>

<template>
    <HeaderComponent />
    <section class="page">
        <!-- Фильтры и поиск (те же самые) -->
        <div class="filters">
            <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Поиск заведений..."
                class="search-input"
            />
            
            <select v-model="selectedCategory" class="filter-select">
                <option value="">Все категории</option>
                <option v-for="cat in categories" :key="cat" :value="cat">
                    {{ cat }}
                </option>
            </select>
            
            <select v-model="sortBy" class="filter-select">
                <option value="rating">По рейтингу</option>
                <option value="title">По названию</option>
                <option value="price">По цене</option>
            </select>
            
            <select v-model="sortOrder" class="filter-select">
                <option value="desc">По убыванию</option>
                <option value="asc">По возрастанию</option>
            </select>
            
            <button @click="resetFilters" class="reset-btn">
                Сбросить
            </button>
            
            <button @click="view = view === 'grid' ? 'list' : 'grid'" class="view-toggle">
                {{ view === 'grid' ? '📋 Список' : '🔲 Сетка' }}
            </button>
        </div>

        <!-- Отображение продуктов из облачной БД -->
        <div :class="['grid', { list: !isGrid() }]">
            <article 
                v-for="p in products" 
                :key="p.id" 
                class="card"
                @click="goToProduct(p.id)"
            >
                <img :src="p.img" :alt="p.title" loading="lazy"/>
                <h3>{{ p.title }}</h3>
                <p class="rating">
                    ★ {{ p.rating }}
                </p>
                <p v-if="p.price" class="price">
                    от {{ p.price }} ₽
                </p>
            </article>
        </div>

        <!-- Пагинация -->
        <div v-if="pagination && pagination.last_page > 1" class="pagination">
            <button 
                @click="changePage(pagination.prev_page_url)" 
                :disabled="!pagination.prev_page_url"
                class="page-btn"
            >
                ← Назад
            </button>
            
            <span class="page-info">
                Страница {{ pagination.current_page }} из {{ pagination.last_page }}
            </span>
            
            <button 
                @click="changePage(pagination.next_page_url)" 
                :disabled="!pagination.next_page_url"
                class="page-btn"
            >
                Вперед →
            </button>
        </div>
    </section>
</template>

<style scoped>

.page {
    padding: 20px;
    min-height: 100vh;
    background-color: #f8f9fa;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.filters {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    align-items: center;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.search-input {
    flex: 1;
    min-width: 200px;
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #42b983;
    box-shadow: 0 0 0 3px rgba(66, 185, 131, 0.1);
}

.filter-select {
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    background: white;
    cursor: pointer;
}

.reset-btn, .view-toggle {
    padding: 10px 20px;
    background: #f5f5f5;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s ease;
}

.reset-btn:hover, .view-toggle:hover {
    background: #e0e0e0;
    transform: translateY(-2px);
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.grid.list {
    grid-template-columns: 1fr;
}

.card {
    border-radius: 12px;
    padding: 15px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e0e0e0;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 12px;
    background: #f0f0f0;
}

.card h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.rating {
    margin: 0;
    font-size: 16px;
    color: #ff9800;
    font-weight: 500;
}

.price {
    margin: 5px 0 0;
    font-size: 14px;
    color: #42b983;
    font-weight: 600;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 40px;
    padding: 20px;
}

.page-btn {
    padding: 10px 20px;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.page-btn:hover:not(:disabled) {
    background: #42b983;
    color: white;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-info {
    font-size: 16px;
    color: #666;
}

@media (max-width: 768px) {
    .filters {
        flex-direction: column;
    }
    
    .search-input, .filter-select, .reset-btn, .view-toggle {
        width: 100%;
    }
    
    .grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}
</style>