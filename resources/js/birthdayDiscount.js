const MODAL_PREFIX = 'fastbite_birthday_modal_';

export const BIRTHDAY_DISCOUNT_PERCENT = 10;

export function todayKey() {
  const d = new Date();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${m}-${day}`;
}

export function wasBirthdayModalShown(userId) {
  if (!userId || typeof sessionStorage === 'undefined') return true;
  return sessionStorage.getItem(`${MODAL_PREFIX}${userId}_${todayKey()}`) === '1';
}

export function markBirthdayModalShown(userId) {
  if (!userId || typeof sessionStorage === 'undefined') return;
  sessionStorage.setItem(`${MODAL_PREFIX}${userId}_${todayKey()}`, '1');
}

/** @param {string|Date|null|undefined} birthDate */
export function isBirthdayToday(birthDate) {
  if (!birthDate) return false;

  const iso = String(birthDate).trim();
  const match = iso.match(/^(\d{4})-(\d{2})-(\d{2})/);
  const now = new Date();

  if (match) {
    return Number(match[2]) === now.getMonth() + 1 && Number(match[3]) === now.getDate();
  }

  const d = new Date(birthDate);
  if (Number.isNaN(d.getTime())) return false;

  return d.getMonth() === now.getMonth() && d.getDate() === now.getDate();
}

/** Скидка считается только от суммы блюд (без сервисного сбора). */
export function calcBirthdayDiscount(itemsTotal) {
  const n = Number(itemsTotal);
  if (!Number.isFinite(n) || n <= 0) return 0;
  return Math.floor((n * BIRTHDAY_DISCOUNT_PERCENT) / 100);
}

export function shouldShowBirthdayModal(userId, birthDate) {
  return Boolean(userId && isBirthdayToday(birthDate) && !wasBirthdayModalShown(userId));
}
