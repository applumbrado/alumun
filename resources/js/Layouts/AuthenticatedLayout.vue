<template>
    <div class="min-h-screen flex bg-slate-950 text-slate-100">
        <!-- Sidebar (desktop + móvil) -->
        <Sidebar
            :is-collapsed="isCollapsed"
            :sidebar-open="sidebarOpen"
            :full-name="fullName"
            :initials="initials"
            @logout="logout"
            @toggle-collapse="toggleCollapse"
            @navigate="goTo"
            @close-sidebar="sidebarOpen = false"
        />

        <!-- Contenido principal -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-14 border-b border-slate-800 bg-slate-950/80 backdrop-blur flex items-center justify-between px-4 md:px-6">
                <div class="flex items-center gap-2">
                    <button class="md:hidden text-slate-200 bg-slate-800/70 hover:bg-slate-700 rounded-lg p-2" @click="sidebarOpen = true">
                        ☰
                    </button>
                    <h1 class="text-sm md:text-base font-semibold text-slate-100">
                        <slot name="title">Dashboard</slot>
                    </h1>
                </div>

                <div class="hidden sm:flex flex-col items-end text-xs">
                    <span class="font-medium text-slate-100 truncate max-w-[150px]">{{ fullName }}</span>
                    <span class="text-[10px] text-slate-400">Alumbrado Público Municipal</span>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
                <slot />
            </main>
        </div>
    </div>
    <ToastProvider />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import Sidebar from '@/Components/Sidebar.vue'
import ToastProvider from "@/Components/Ui/ToastProvider.vue";

const sidebarOpen = ref(false)
const isCollapsed = ref(false)

const page = usePage()
const user = computed(() => page.props.auth?.user || page.props.user || {})

const fullName = computed(() => user.value.full_name || '')
const initials = computed(() =>
    [user.value.nombre, user.value.ap_paterno]
        .filter(Boolean)
        .map((p) => p[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
)

const logoutForm = useForm({})
const logout = () => logoutForm.post(route('logout'))

const toggleCollapse = () => (isCollapsed.value = !isCollapsed.value)
const goTo = (name) => {
    if (!route().current(name)) window.location.href = route(name)
}
</script>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
