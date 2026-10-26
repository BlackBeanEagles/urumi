import test from 'node:test';
import assert from 'node:assert/strict';
import { requiresChatApproval } from '../src/chat-policy.js';

test('chat tool policy only auto-runs explicitly reviewed read-only tools',()=>{
  for(const name of [
    'woocommerce__list_products',
    'woocommerce__list_orders',
    'woocommerce__get_order',
    'catalog__store_info',
    'clock__current_time'
  ]) assert.equal(requiresChatApproval(name),false,name);
  for(const name of [
    'woocommerce__create_product',
    'woocommerce__update_order_status',
    'woocommerce__list_products_and_delete',
    'new_server__sync_everything',
    'clock__current_time_and_write'
  ]) assert.equal(requiresChatApproval(name),true,name);
});
