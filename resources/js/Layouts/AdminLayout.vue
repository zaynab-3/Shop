<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';
import {
    Boxes,
    FileText,
    FolderTree,
    Home,
    LayoutDashboard,
    LogOut,
    Menu,
    Settings,
    ShoppingBag,
    Store,
    X,
} from 'lucide-vue-next';
import { ref } from 'vue';

const showingSidebar = ref(false);

const navItems = [
    { name: 'Dashboard', href: route('admin.dashboard'), active: route().current('admin.dashboard'), icon: LayoutDashboard },
    { name: 'Home Page', href: route('admin.homepage.index'), active: route().current('admin.homepage.*'), icon: Home },
    { name: 'Products', href: route('admin.products.index'), active: route().current('admin.products.*'), icon: Boxes },
    { name: 'Categories', href: route('admin.categories.index'), active: route().current('admin.categories.*'), icon: FolderTree },
    { name: 'Orders', href: route('admin.orders.index'), active: route().current('admin.orders.*'), icon: ShoppingBag },
    { name: 'Pages', href: route('admin.pages.index'), active: route().current('admin.pages.*'), icon: FileText },
    { name: 'Settings', href: route('admin.settings.index'), active: route().current('admin.settings.*'), icon: Settings },
];
</script>

<template>
    <div class="admin-shell">
        <div
            v-if="showingSidebar"
            class="admin-sidebar-overlay"
            @click="showingSidebar = false"
        />

        <aside :class="['admin-sidebar', showingSidebar ? 'is-open' : '']">
            <div class="admin-sidebar-brand">
                <Link href="/" class="admin-brand-mark">SCARBINA</Link>
                <button class="admin-sidebar-close" type="button" @click="showingSidebar = false" aria-label="Close admin menu">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <nav class="admin-sidebar-nav" aria-label="Admin navigation">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    :class="['admin-nav-link', item.active ? 'is-active' : '']"
                    @click="showingSidebar = false"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <div class="admin-sidebar-footer">
                <Link href="/" class="admin-store-link">
                    <Store class="h-4 w-4" />
                    Back to Store
                </Link>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <button class="admin-menu-button" type="button" @click="showingSidebar = true" aria-label="Open admin menu">
                    <Menu class="h-5 w-5" />
                </button>

                <div class="admin-topbar-title">
                    <slot name="header" />
                </div>

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button type="button" class="admin-user-button">
                            <span>{{ $page.props.auth.user.name }}</span>
                        </button>
                    </template>

                    <template #content>
                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">
                            <span class="inline-flex items-center gap-2">
                                <LogOut class="h-4 w-4" />
                                Log Out
                            </span>
                        </DropdownLink>
                    </template>
                </Dropdown>
            </header>

            <main class="admin-content">
                <slot />
            </main>
        </div>
    </div>
</template>
