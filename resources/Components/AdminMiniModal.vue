<template>
  <div v-if="show" class="admin-mini-modal-overlay" @click="onOverlayClick">
    <div class="admin-mini-modal" @click.stop>
      <h3 class="admin-mini-modal-title">{{ title }}</h3>
      <p v-if="message" class="admin-mini-modal-message">{{ message }}</p>
      <div class="admin-mini-modal-actions">
        <template v-if="mode === 'confirm'">
          <button type="button" class="admin-mini-modal-btn admin-mini-modal-btn--primary" @click="$emit('confirm')">
            {{ confirmLabel }}
          </button>
          <button type="button" class="admin-mini-modal-btn admin-mini-modal-btn--ghost" @click="$emit('cancel')">
            {{ cancelLabel }}
          </button>
        </template>
        <button
          v-else
          type="button"
          class="admin-mini-modal-btn admin-mini-modal-btn--primary"
          @click="$emit('close')"
        >
          {{ okLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminMiniModal',
  props: {
    show: { type: Boolean, default: false },
    mode: {
      type: String,
      default: 'confirm',
      validator: v => ['confirm', 'notice'].includes(v),
    },
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Удалить' },
    cancelLabel: { type: String, default: 'Отмена' },
    okLabel: { type: String, default: 'Понятно' },
    closeOnOverlay: { type: Boolean, default: true },
  },
  emits: ['confirm', 'cancel', 'close'],
  methods: {
    onOverlayClick() {
      if (!this.closeOnOverlay) return;
      if (this.mode === 'confirm') {
        this.$emit('cancel');
      } else {
        this.$emit('close');
      }
    },
  },
};
</script>

<style scoped>
.admin-mini-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 3000;
  backdrop-filter: blur(3px);
}
.admin-mini-modal {
  background: #fff;
  border-radius: 16px;
  width: 90%;
  max-width: 400px;
  padding: 1.25rem 1.5rem;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
}
.admin-mini-modal-title {
  margin: 0 0 10px;
  font-size: 18px;
  color: #1e1e1e;
}
.admin-mini-modal-message {
  margin: 0 0 18px;
  font-size: 14px;
  line-height: 1.5;
  color: #555;
}
.admin-mini-modal-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}
.admin-mini-modal-btn {
  padding: 10px 18px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  border: none;
}
.admin-mini-modal-btn--primary {
  background: #ff6b00;
  color: #fff;
}
.admin-mini-modal-btn--primary:hover {
  background: #e05e00;
}
.admin-mini-modal-btn--ghost {
  background: #fff;
  color: #666;
  border: 2px solid #eee;
}
</style>
