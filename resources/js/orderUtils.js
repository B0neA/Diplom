import {
  ORDER_SERVICE_FEE,
  calcDeliveryFee,
  calcOrderTotalWithFees,
} from './deliveryFee.js';

export { ORDER_SERVICE_FEE, calcDeliveryFee, calcOrderTotalWithFees };

const WINDOW_MS = 5 * 60 * 1000;

export function parseOrderItems(order) {
  if (!order?.items) return [];
  if (typeof order.items === 'string') {
    try {
      return JSON.parse(order.items);
    } catch {
      return [];
    }
  }
  return Array.isArray(order.items) ? order.items : [];
}

export function getOrderRestaurantIds(order) {
  if (order?.restaurant_id) {
    return [Number(order.restaurant_id)];
  }
  const ids = new Set();
  for (const item of parseOrderItems(order)) {
    const rid = item.restaurantId ?? item.restaurant_id;
    if (rid) ids.add(Number(rid));
  }
  return [...ids];
}

export function groupOrderItemsByRestaurant(order) {
  const items = parseOrderItems(order);
  if (!items.length) return [];

  const defaultRid = order?.restaurant_id ? Number(order.restaurant_id) : null;
  const groupsMap = new Map();

  for (const item of items) {
    const rid = Number(item.restaurantId ?? item.restaurant_id ?? defaultRid ?? 0) || 0;
    const key = rid || 'unknown';
    if (!groupsMap.has(key)) {
      groupsMap.set(key, { restaurantId: rid || null, items: [] });
    }
    groupsMap.get(key).items.push(item);
  }

  return [...groupsMap.values()];
}

export function isSingleRestaurantOrder(order) {
  return getOrderRestaurantIds(order).length <= 1;
}

export function canModifyOrder(order, now = Date.now()) {
  if (!order || order.status === 'cancelled') return false;
  if (order.status !== 'new') return false;
  const created = new Date(order.created_at).getTime();
  if (Number.isNaN(created)) return false;
  return now - created <= WINDOW_MS;
}

export function canEditOrder(order, now = Date.now()) {
  return canModifyOrder(order, now) && isSingleRestaurantOrder(order);
}

export function secondsRemaining(order, now = Date.now()) {
  const created = new Date(order.created_at).getTime();
  if (Number.isNaN(created)) return 0;
  return Math.max(0, Math.ceil((WINDOW_MS - (now - created)) / 1000));
}

export function formatCountdown(totalSeconds) {
  const m = Math.floor(totalSeconds / 60);
  const s = totalSeconds % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
}

export function calcItemsTotal(items) {
  return items.reduce((sum, i) => sum + (Number(i.price) || 0) * (Number(i.quantity) || 0), 0);
}

export function calcOrderTotal(items) {
  return calcOrderTotalWithFees(calcItemsTotal(items));
}
