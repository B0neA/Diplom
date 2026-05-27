<template>
  <div class="not-found">
    <HeaderComponent />
    <div class="not-found__inner container">
      <h1>Страница не найдена</h1>
      <p>Такой страницы нет. Проверьте адрес или вернитесь на главную.</p>
      <a href="/" class="not-found__btn">На главную</a>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import HeaderComponent from '../../Components/HeaderComponent.vue';
import { usePageSeo } from '../usePageSeo.js';

export default {
  name: 'NotFoundPage',
  components: { HeaderComponent, Head },
  setup() {
    const seo = usePageSeo('Страница не найдена — Лопать Подано', 'Запрошенная страница не существует. Вернитесь на главную Лопать Подано и выберите ресторан.');
    return { seo };
  },
};
</script>

<style scoped>
.not-found { min-height: 100vh; background: #fefaf5; }
.not-found__inner {
  max-width: 560px;
  margin: 0 auto;
  padding: 4rem 1.5rem;
  text-align: center;
}
.not-found h1 { font-size: 2rem; margin-bottom: 1rem; color: #1e1e1e; }
.not-found p { color: #666; margin-bottom: 2rem; line-height: 1.5; }
.not-found__btn {
  display: inline-block;
  padding: 12px 28px;
  background: #ff6b00;
  color: #fff;
  border-radius: 40px;
  font-weight: 600;
  text-decoration: none;
}
.not-found__btn:hover { background: #e05e00; }
</style>
