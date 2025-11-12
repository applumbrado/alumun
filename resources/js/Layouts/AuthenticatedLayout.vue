<template>
    <div class="min-h-screen flex bg-slate-950 text-slate-100">
        <!-- Sidebar -->
        <aside
            :class="[
        'transition-all duration-300 bg-gradient-to-b from-alumun-guinda via-alumun-pino to-black shadow-2xl shadow-black/60',
        isCollapsed ? 'w-20' : 'w-64',
        'hidden md:flex flex-col'
      ]"
        >
            <div class="flex items-center justify-between px-4 py-4 border-b border-white/10">
                <div class="flex items-center gap-2" :class="isCollapsed && 'justify-center w-full'">
                    <div class="h-10 w-10 rounded-2xl bg-black/30 flex items-center justify-center">
                        <span class="font-bold text-sm">AP</span>
                    </div>
                    <div v-if="!isCollapsed" class="leading-tight">
                        <span class="font-semibold tracking-wide">AluMun</span>
                        <span class="block text-[10px] text-white/70">Alumbrado Público</span>
                    </div>
                </div>

                <button
                    v-if="!isCollapsed"
                    class="text-white/70 hover:text-white text-xs bg-black/20 hover:bg-black/30 rounded-full p-1.5 ml-2"
                    @click="toggleCollapse"
                >←</button>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 space-y-2">
                <SidebarItem icon="🏠" label="Inicio" :active="route().current('dashboard')" :collapsed="isCollapsed" @click="goTo('dashboard')" />
                <SidebarItem icon="💡" label="Reportes" :collapsed="isCollapsed" />
                <SidebarItem icon="📊" label="Tableros" :collapsed="isCollapsed" />
                <SidebarItem icon="⚙️" label="Configuración" :collapsed="isCollapsed" />
            </nav>

            <div class="border-t border-white/10 px-3 py-3">
                <div class="flex items-center gap-2 mb-2">
                    <div class="h-8 w-8 rounded-full bg-black/30 flex items-center justify-center text-xs">{{ initials }}</div>
                    <div v-if="!isCollapsed" class="flex flex-col">
                        <span class="text-xs font-semibold truncate">{{ fullName }}</span>
                        <span class="text-[10px] text-white/70">Usuario del sistema</span>
                    </div>
                </div>
                <button class="w-full flex items-center justify-center gap-2 text-xs bg-black/30 hover:bg-black/40 rounded-xl px-3 py-2" @click="logout">
                    <span>Salir</span>
                </button>
            </div>
        </aside>

        <!-- Sidebar móvil -->
        <transition name="fade">
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/60 md:hidden" @click="sidebarOpen=false">
                <aside class="absolute inset-y-0 left-0 w-64 bg-gradient-to-b from-alumun-guinda via-alumun-pino to-black shadow-2xl flex flex-col" @click.stop>
                    <div class="flex items-center justify-between px-4 py-4 border-b border-white/10">
                        <div class="flex items-center gap-2">
                            <div class="h-9 w-9 rounded-2xl bg-black/30 flex items-center justify-center">
                                <span class="font-bold text-sm">AP</span>
                            </div>
                            <div class="leading-tight">
                                <span class="font-semibold tracking-wide">AluMun</span>
                                <span class="block text-[10px] text-white/70">Alumbrado Público</span>
                            </div>
                        </div>
                        <button class="text-white/70 hover:text-white text-xs bg-black/20 hover:bg-black/30 rounded-full p-1.5" @click="sidebarOpen=false">✕</button>
                    </div>

                    <nav class="flex-1 overflow-y-auto py-4 space-y-2">
                        <SidebarItem icon="🏠" label="Inicio" :active="route().current('dashboard')" @click="goTo('dashboard'); sidebarOpen=false" />
                        <SidebarItem icon="💡" label="Reportes" />
                        <SidebarItem icon="📊" label="Tableros" />
                        <SidebarItem icon="⚙️" label="Configuración" />
                    </nav>

                    <div class="border-t border-white/10 px-3 py-3">
                        <button class="w-full flex items-center justify-center gap-2 text-xs bg-black/30 hover:bg-black/40 rounded-xl px-3 py-2" @click="logout">
                            <span>Salir</span>
                        </button>
                    </div>
                </aside>
            </div>
        </transition>

        <!-- Contenido principal -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-14 border-b border-slate-800 bg-slate-950/80 backdrop-blur flex items-center justify-between px-4 md:px-6">
                <div class="flex items-center gap-2">
                    <button class="md:hidden text-slate-200 bg-slate-800/70 hover:bg-slate-700 rounded-lg p-2" @click="sidebarOpen=true">☰</button>
                    <h1 class="text-sm md:text-base font-semibold text-slate-100"><slot name="title">Dashboard</slot></h1>
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
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SidebarItem from '@/Components/SidebarItem.vue';

const sidebarOpen = ref(false);
const isCollapsed = ref(false);

const page = usePage();
const user = computed(() => page.props.auth?.user || page.props.user || {});

const fullName = computed(() => [user.value.name, user.value.apellido_paterno, user.value.apellido_materno].filter(Boolean).join(' '));
const initials = computed(() => [user.value.name, user.value.apellido_paterno].filter(Boolean).map(p=>p[0]).join('').toUpperCase().slice(0,2));

const logoutForm = useForm({});
const logout = () => logoutForm.post(route('logout'));

const toggleCollapse = () => isCollapsed.value = !isCollapsed.value;
const goTo = (name) => { if (!route().current(name)) window.location.href = route(name); };
</script>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .2s}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
