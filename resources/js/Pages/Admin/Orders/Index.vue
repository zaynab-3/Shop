<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    orders: Array,
    orderStatuses: Array,
    paymentStatuses: Array,
});

const pendingCount = computed(() => props.orders.filter((order) => order.order_status === 'pending').length);
const unpaidCount = computed(() => props.orders.filter((order) => order.payment_status === 'unpaid').length);
</script>

<template>
    <Head title="Orders - Admin" />

    <AdminLayout>
        <template #header>
            <div class="admin-page-heading">
                <div>
                    <p class="admin-eyebrow">Operations</p>
                    <h1>Orders</h1>
                </div>
            </div>
        </template>

        <div class="admin-stack">
            <div v-if="$page.props.flash?.success" class="admin-success">
                {{ $page.props.flash.success }}
            </div>

            <div class="admin-metrics-grid">
                <div class="admin-stat">
                    <p>Total orders</p>
                    <strong>{{ orders.length }}</strong>
                </div>
                <div class="admin-stat">
                    <p>Pending orders</p>
                    <strong>{{ pendingCount }}</strong>
                </div>
                <div class="admin-stat">
                    <p>Unpaid orders</p>
                    <strong>{{ unpaidCount }}</strong>
                </div>
            </div>

            <section class="admin-panel admin-table-panel">
                <div class="admin-panel-header">
                    <div>
                        <h3>Order Queue</h3>
                        <p>WhatsApp orders saved before the customer leaves the site.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Order Status</th>
                                <th>Payment Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in orders" :key="order.id">
                                <td>
                                    <div class="font-semibold text-gray-950">{{ order.order_number }}</div>
                                    <div class="text-xs text-gray-500">{{ order.created_at }}</div>
                                </td>
                                <td>
                                    <div>{{ order.customer_name }}</div>
                                    <div class="text-xs text-gray-500">{{ order.customer_phone }}</div>
                                </td>
                                <td>{{ order.total }} {{ order.currency }}</td>
                                <td>
                                    <span class="admin-status-pill is-muted">{{ order.order_status }}</span>
                                </td>
                                <td>
                                    <span class="admin-status-pill is-muted">{{ order.payment_status }}</span>
                                </td>
                                <td>
                                    <div class="admin-row-actions">
                                        <Link :href="route('admin.orders.show', order.id)" class="admin-icon-action" aria-label="View order">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="orders.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No orders found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
