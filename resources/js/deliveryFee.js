/** Бесплатная доставка от этой суммы (только блюда, без сборов). */
export const FREE_DELIVERY_FROM = 800;

export const ORDER_SERVICE_FEE = 50;

/** Пороги: чем больше сумма блюд, тем ниже доставка. */
const DELIVERY_TIERS = [
  { from: 800, fee: 0 },
  { from: 600, fee: 99 },
  { from: 400, fee: 149 },
  { from: 0, fee: 199 },
];

/**
 * Стоимость доставки по сумме блюд в корзине.
 * @param {number} itemsSubtotal — сумма товаров без доставки и сервисного сбора
 */
export function calcDeliveryFee(itemsSubtotal) {
  const amount = Math.max(0, Math.round(Number(itemsSubtotal) || 0));
  for (const tier of DELIVERY_TIERS) {
    if (amount >= tier.from) {
      return tier.fee;
    }
  }
  return 199;
}

/** Сколько не хватает до бесплатной доставки (0 — уже бесплатно). */
export function amountUntilFreeDelivery(itemsSubtotal) {
  const amount = Math.max(0, Math.round(Number(itemsSubtotal) || 0));
  if (amount >= FREE_DELIVERY_FROM) {
    return 0;
  }
  return FREE_DELIVERY_FROM - amount;
}

export function formatDeliveryFee(fee) {
  return fee <= 0 ? 'Бесплатно' : `${fee} ₽`;
}

/** Итого: блюда + доставка + сервисный сбор. */
export function calcOrderTotalWithFees(itemsSubtotal) {
  const subtotal = Math.max(0, Math.round(Number(itemsSubtotal) || 0));
  return subtotal + calcDeliveryFee(subtotal) + ORDER_SERVICE_FEE;
}
