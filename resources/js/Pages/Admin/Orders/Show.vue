<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    order: Object,
    orderStatuses: Array,
    paymentStatuses: Array,
});

const statusForm = useForm({
    order_status: props.order.order_status,
    payment_status: props.order.payment_status,
});

const selectedItems = computed(() => props.order.items || []);

function saveStatus() {
    statusForm.patch(route('admin.orders.update', props.order.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`${order.order_number} - Admin`" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <Link :href="route('admin.orders.index')" class="admin-back-link">
                        <ArrowLeft class="h-4 w-4" />
                        Orders
                    </Link>
                    <h1>{{ order.order_number }}</h1>
                </div>
                <button class="admin-primary-button" type="button" :disabled="statusForm.processing" @click="saveStatus">
                    <Save class="h-4 w-4" />
                    {{ statusForm.processing ? 'Saving...' : 'Save Status' }}
                </button>
            </div>
        </template>

        <div class="admin-form-layout">
            <div class="admin-form-main">
                <div v-if="$page.props.flash?.success" class="admin-success">
                    {{ $page.props.flash.success }}
                </div>

                <section class="admin-form-card">
                    <div class="admin-form-card-header">
                        <h3>Customer</h3>
                        <p>{{ order.customer_name }} - {{ order.customer_phone }}</p>
                    </div>
                    <div class="admin-detail-grid">
                        <div><span>Area</span><strong>{{ order.customer_area }}</strong></div>
                        <div><span>Address</span><strong>{{ order.customer_address }}</strong></div>
                        <div v-if="order.customer_notes" class="span-2"><span>Notes</span><strong>{{ order.customer_notes }}</strong></div>
                    </div>
                </section>

                <section class="admin-form-card">
                    <div class="admin-form-card-header">
                        <h3>Items</h3>
                        <p>Order snapshots stay correct even if products change later.</p>
                    </div>
                    <div class="admin-order-items">
                        <div v-for="item in selectedItems" :key="item.id" class="admin-order-item">
                            <div>
                                <p>{{ item.product_name_snapshot }}</p>
                                <span>{{ [item.selected_color, item.selected_size].filter(Boolean).join(' / ') || item.sku_snapshot || 'No variant' }}</span>
                            </div>
                            <strong>{{ item.quantity }} x {{ item.unit_price }} {{ order.currency }}</strong>
                        </div>
                    </div>
                    <div class="admin-order-total">
                        <span>Total</span>
                        <strong>{{ order.total }} {{ order.currency }}</strong>
                    </div>
                </section>

                <section v-if="order.whatsapp_message" class="admin-form-card">
                    <div class="admin-form-card-header">
                        <h3>WhatsApp Receipt</h3>
                    </div>
                    <pre class="admin-receipt">{{ order.whatsapp_message }}</pre>
                </section>
            </div>

            <aside class="admin-form-side">
                <section class="admin-form-card">
                    <h3>Status</h3>
                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Order status</span>
                            <select v-model="statusForm.order_status" class="form-input">
                                <option v-for="status in orderStatuses" :key="status" :value="status">{{ status }}</option>
                            </select>
                        </label>
                        <label class="admin-field">
                            <span>Payment status</span>
                            <select v-model="statusForm.payment_status" class="form-input">
                                <option v-for="status in paymentStatuses" :key="status" :value="status">{{ status }}</option>
                            </select>
                        </label>
                    </div>
                </section>

                <section class="admin-form-card">
                    <h3>Payment</h3>
                    <div class="admin-detail-grid single">
                        <div><span>Method</span><strong>{{ order.payment_method }}</strong></div>
                        <div><span>Subtotal</span><strong>{{ order.subtotal }} {{ order.currency }}</strong></div>
                        <div><span>Delivery</span><strong>{{ order.delivery_fee }} {{ order.currency }}</strong></div>
                        <div><span>Discount</span><strong>{{ order.discount_total }} {{ order.currency }}</strong></div>
                    </div>
                </section>
            </aside>
        </div>
    </AdminLayout>
</template>
