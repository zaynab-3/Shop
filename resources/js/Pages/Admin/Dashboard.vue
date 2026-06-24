<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    recentOrders: Array,
    inventory: Array,
    pages: Array,
    settings: Object,
});
</script>

<template>
    <Head title="SCARBINA Admin" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">Dashboard</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
                <div class="grid gap-4 md:grid-cols-5">
                    <div v-for="(value, key) in stats" :key="key" class="admin-stat">
                        <p>{{ key.replace(/([A-Z])/g, ' $1') }}</p>
                        <strong>{{ value }}</strong>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
                    <section class="admin-panel">
                        <h3>Recent Orders</h3>
                        <div class="mt-4 overflow-x-auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="order in recentOrders" :key="order.order_number">
                                        <td>{{ order.order_number }}</td>
                                        <td>{{ order.customer_name }}</td>
                                        <td>{{ order.total }} {{ order.currency }}</td>
                                        <td>{{ order.order_status }} / {{ order.payment_status }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="admin-panel">
                        <h3>Prepared Pages</h3>
                        <div class="mt-4 grid gap-3">
                            <div v-for="page in pages" :key="page.page_key" class="admin-list-row">
                                <div>
                                    <p class="font-medium">{{ page.title }}</p>
                                    <p class="text-xs text-gray-500">{{ page.page_key }}</p>
                                </div>
                                <span>{{ page.is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="admin-panel">
                    <h3>Inventory Watch</h3>
                    <div class="mt-4 overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Public</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product in inventory" :key="product.id">
                                    <td>{{ product.title }}</td>
                                    <td>{{ product.sku || '-' }}</td>
                                    <td>{{ product.stock_quantity }}</td>
                                    <td>{{ product.stock_status }}</td>
                                    <td>{{ product.is_active ? 'Yes' : 'No' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
        </div>
    </AdminLayout>
</template>
