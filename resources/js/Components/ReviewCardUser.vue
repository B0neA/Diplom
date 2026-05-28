<template>
  <div class="review-card-user">
    <template v-if="editing">
      <select :value="editRating" class="rating-select" @change="$emit('update:editRating', Number($event.target.value))">
        <option v-for="n in 5" :key="n" :value="n">{{ n }} / 5</option>
      </select>
      <textarea :value="editComment" rows="3" @input="$emit('update:editComment', $event.target.value)"></textarea>
      <div class="mini-actions">
        <button type="button" @click="$emit('save')">Сохранить</button>
        <button type="button" class="cancel" @click="$emit('cancel')">Отмена</button>
      </div>
    </template>
    <template v-else>
      <div class="review-head">
        <strong>{{ review.author_name }}</strong>
        <span class="stars" :style="starMaskVars">
          <span
            v-for="n in 5"
            :key="`star-${n}`"
            :class="['rating-star', { active: n <= Number(review.rating || 0) }]"
          />
        </span>
        <div v-if="canEdit" class="review-btns">
          <button type="button" class="edit-link" @click="$emit('edit')">Изменить</button>
          <button type="button" class="danger-link" @click="$emit('delete')">Удалить</button>
        </div>
      </div>
      <p v-if="review.comment">{{ review.comment }}</p>
      <div class="review-meta">
        <small>{{ formatDate(displayDate) }}</small>
        <span v-if="isEdited" class="edited-badge">
          <img v-if="redactIcon" :src="redactIcon" alt="" class="edited-icon" />
          отредактировано
        </span>
      </div>
    </template>
  </div>
</template>

<script>
export default {
  name: 'ReviewCardUser',
  props: {
    review: { type: Object, required: true },
    canEdit: { type: Boolean, default: false },
    editing: { type: Boolean, default: false },
    editRating: { type: Number, default: 5 },
    editComment: { type: String, default: '' },
    redactIcon: { type: String, default: '' },
    starIcon: { type: String, default: '' },
  },
  emits: ['edit', 'save', 'cancel', 'delete', 'update:editRating', 'update:editComment'],
  computed: {
    isEdited() {
      if (!this.review?.updated_at) return false;
      if (!this.review?.created_at) return true;
      return new Date(this.review.updated_at).getTime() > new Date(this.review.created_at).getTime() + 1000;
    },
    displayDate() {
      return this.isEdited ? this.review.updated_at : this.review.created_at;
    },
    starMaskVars() {
      if (!this.starIcon) return {};
      return { '--star-mask': `url("${this.starIcon}")` };
    },
  },
  methods: {
    formatDate(d) {
      return d ? new Date(d).toLocaleString('ru-RU') : '';
    },
  },
};
</script>

<style scoped>
.review-card-user {
  background: #fefaf5;
  border-radius: 12px;
  padding: 14px;
  margin-bottom: 12px;
}
.review-head {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}
.stars {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.rating-star {
  width: 15px;
  height: 15px;
  border-radius: 2px;
  background: #c3c3c3;
  display: inline-block;
}
.stars[style*="--star-mask"] .rating-star {
  -webkit-mask-image: var(--star-mask);
  mask-image: var(--star-mask);
  -webkit-mask-size: contain;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
}
.rating-star.active {
  background: var(--brand-gradient);
}
.review-btns { margin-left: auto; display: flex; gap: 8px; }
.edit-link, .danger-link {
  background: none;
  border: none;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
}
.edit-link { color: #ff6b00; }
.danger-link { color: #e74c3c; }
.review-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 6px;
}
.review-meta small { color: #888; font-size: 12px; }
.edited-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #888;
  font-style: italic;
}
.edited-icon { width: 14px; height: 14px; object-fit: contain; }
.rating-select, textarea {
  width: 100%;
  margin-bottom: 8px;
  padding: 8px;
  border: 1px solid #eee;
  border-radius: 8px;
  box-sizing: border-box;
}
.mini-actions { display: flex; gap: 8px; }
.mini-actions button {
  padding: 8px 14px;
  background: #ff6b00;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}
.mini-actions .cancel { background: #fff; color: #666; border: 1px solid #eee; }
</style>
