<template>
  <div v-if="show" class="archive-modal-overlay" @click="$emit('close')">
    <div class="archive-modal" @click.stop>
      <div class="archive-modal-header">
        <h3>{{ title }}</h3>
        <button type="button" class="archive-modal-close" aria-label="Закрыть" @click="$emit('close')">×</button>
      </div>

      <p v-if="hint" class="archive-modal-hint">{{ hint }}</p>

      <div v-if="loading" class="archive-modal-status">Загрузка архива...</div>
      <p v-else-if="!items.length" class="archive-modal-status archive-modal-status--empty">
        {{ emptyText }}
      </p>
      <div v-else class="table-wrap archive-modal-table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Название</th>
              <th v-if="showProductsCount">Блюд</th>
              <th>Удалён</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="`${item.type}-${item.id}`">
              <td>{{ item.id }}</td>
              <td>{{ item.title }}</td>
              <td v-if="showProductsCount">{{ item.products_count ?? '—' }}</td>
              <td>{{ formatDate(item.deleted_at) }}</td>
              <td class="actions-cell">
                <button type="button" class="restore-btn" @click="$emit('restore', item)">Восстановить</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="archive-modal-footer">
        <button type="button" class="archive-modal-ok" @click="$emit('close')">Закрыть</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminArchiveModal',
  props: {
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Архив' },
    hint: { type: String, default: '' },
    items: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    emptyText: { type: String, default: 'Архив пуст' },
    showProductsCount: { type: Boolean, default: false },
  },
  emits: ['close', 'restore'],
  methods: {
    formatDate(iso) {
      return iso ? new Date(iso).toLocaleString('ru-RU') : '—';
    },
  },
};
</script>

<style scoped>
.archive-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 3000;
  backdrop-filter: blur(3px);
  padding: 1rem;
}
.archive-modal {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 640px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
}
.archive-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem 0;
}
.archive-modal-header h3 {
  margin: 0;
  font-size: 20px;
  color: #1e1e1e;
}
.archive-modal-close {
  border: none;
  background: #f5f5f5;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  color: #666;
}
.archive-modal-close:hover {
  background: #eee;
  color: #333;
}
.archive-modal-hint {
  margin: 8px 1.5rem 0;
  font-size: 13px;
  color: #888;
}
.archive-modal-status {
  margin: 1.5rem;
  text-align: center;
  font-size: 15px;
  color: #666;
}
.archive-modal-status--empty {
  color: #999;
  font-style: italic;
  padding: 2rem 1rem;
}
.archive-modal-table-wrap {
  margin: 1rem 1.5rem 0;
  overflow: auto;
  max-height: 50vh;
}
.archive-modal-table-wrap table {
  width: 100%;
  border-collapse: collapse;
}
.archive-modal-table-wrap th,
.archive-modal-table-wrap td {
  padding: 10px 12px;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
}
.actions-cell {
  white-space: nowrap;
}
.restore-btn {
  border: none;
  background: #ff6b00;
  color: #fff;
  padding: 6px 14px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
}
.restore-btn:hover {
  background: #e05e00;
}
.archive-modal-footer {
  padding: 1rem 1.5rem 1.25rem;
  display: flex;
  justify-content: flex-end;
}
.archive-modal-ok {
  padding: 10px 20px;
  border: none;
  background: #ff6b00;
  color: #fff;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}
.archive-modal-ok:hover {
  background: #e05e00;
}
</style>
