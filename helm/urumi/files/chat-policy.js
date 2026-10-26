// Exact read-only chat tools. Anything new or unknown requires confirmation.
export const READ_ONLY_CHAT_TOOLS = new Set([
  'woocommerce__list_products',
  'woocommerce__list_orders',
  'woocommerce__get_order',
  'catalog__store_info',
  'clock__current_time',
]);

export const requiresChatApproval = name => !READ_ONLY_CHAT_TOOLS.has(name);
