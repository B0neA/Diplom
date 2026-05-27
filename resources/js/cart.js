export const GUEST_CART_KEY = 'shoppingCart_guest';
export const LEGACY_CART_KEY = 'shoppingCart';

let activeUserId = null;

export function setActiveCartUser(userId) {
  activeUserId = userId || null;
}

export function getActiveCartUserId() {
  return activeUserId;
}

export function getCartStorageKey(userId = activeUserId) {
  if (!userId) return null;
  return `shoppingCart_${userId}`;
}

function readCart(storage, key) {
  try {
    return JSON.parse(storage.getItem(key) || '[]');
  } catch {
    return [];
  }
}

function writeCart(storage, key, cart) {
  storage.setItem(key, JSON.stringify(cart));
}

function mergeCartItems(existing, incoming) {
  const items = [...existing];
  for (const item of incoming) {
    const found = items.find(i => i.id === item.id);
    if (found) found.quantity += item.quantity || 1;
    else items.push({ ...item });
  }
  return items;
}

export function getCart() {
  if (activeUserId) {
    const key = getCartStorageKey(activeUserId);
    return key ? readCart(localStorage, key) : [];
  }
  return readCart(sessionStorage, GUEST_CART_KEY);
}

export function saveCart(cart) {
  if (activeUserId) {
    const key = getCartStorageKey(activeUserId);
    if (key) writeCart(localStorage, key, cart);
  } else {
    writeCart(sessionStorage, GUEST_CART_KEY, cart);
  }
}

export function notifyCartChanged() {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('cart-updated'));
  }
}

export function clearCartForLogout() {
  setActiveCartUser(null);
  sessionStorage.removeItem(GUEST_CART_KEY);
  localStorage.removeItem(LEGACY_CART_KEY);
  notifyCartChanged();
}

export async function syncCartWithAuth() {
  const { getCurrentUser } = await import('./supabase.js');
  const user = await getCurrentUser();
  const userId = user?.id || null;

  if (userId) {
    const guestCart = readCart(sessionStorage, GUEST_CART_KEY);
    const userKey = getCartStorageKey(userId);
    if (guestCart.length && userKey) {
      const userCart = readCart(localStorage, userKey);
      writeCart(localStorage, userKey, mergeCartItems(userCart, guestCart));
      sessionStorage.removeItem(GUEST_CART_KEY);
    }

    const legacy = localStorage.getItem(LEGACY_CART_KEY);
    if (legacy && userKey) {
      if (!localStorage.getItem(userKey)) {
        localStorage.setItem(userKey, legacy);
      }
      localStorage.removeItem(LEGACY_CART_KEY);
    }
  }

  setActiveCartUser(userId);
  notifyCartChanged();
  return userId;
}

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
