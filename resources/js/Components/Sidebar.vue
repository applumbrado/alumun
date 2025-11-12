<script setup>
import SidebarItem from '@/Components/SidebarItem.vue'

defineProps({
    isCollapsed: { type: Boolean, default: false },
    sidebarOpen: { type: Boolean, default: false },
    fullName: { type: String, default: '' },
    initials: { type: String, default: '' },
})
</script>

<template>
    <aside
        :class="[
            'transition-all duration-300 bg-gradient-to-b from-alumun-guinda via-alumun-pino to-black shadow-2xl shadow-black/60',
            isCollapsed ? 'w-20' : 'w-64',
            'hidden md:flex flex-col'
        ]"
    >
        <!-- Encabezado -->
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

            <!-- 🔄 Botón dinámico -->
            <button
                class="cursor-pointer text-white/70 hover:text-white text-xs bg-black/20 hover:bg-black/30 rounded-full p-1.5 ml-2 transition-transform duration-300"
                :class="{ 'rotate-180': isCollapsed }"
                @click="$emit('toggle-collapse')"
                :title="isCollapsed ? 'Expandir menú' : 'Colapsar menú'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 overflow-y-auto py-4 space-y-2">
            <SidebarItem icon="🏠" label="Inicio" :active="route().current('dashboard')" :collapsed="isCollapsed" @click="$emit('navigate', 'dashboard')" />
            <SidebarItem icon="💡" label="Reportes" :collapsed="isCollapsed" />
            <SidebarItem icon="📊" label="Tableros" :collapsed="isCollapsed" />
            <SidebarItem icon="⚙️" label="Configuración" :collapsed="isCollapsed" />
        </nav>

        <!-- Perfil -->
        <div class="border-t border-white/10 px-3 py-3">
            <div class="flex items-center gap-2 mb-2">
                <div class="h-8 w-8 rounded-full bg-black/30 flex items-center justify-center text-xs">{{ initials }}</div>
                <div v-if="!isCollapsed" class="flex flex-col">
                    <span class="text-xs font-semibold truncate">{{ fullName }}</span>
                    <span class="text-[10px] text-white/70">Usuario del sistema</span>
                </div>
            </div>
            <button
                class="cursor-pointer w-full flex items-center justify-center gap-2 text-xs bg-black/30 hover:bg-black/40 rounded-xl px-3 py-2"
                @click="$emit('logout')"
            >
                <span>Salir</span>
            </button>
        </div>

        <!-- Sidebar móvil -->
        <transition name="fade">
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/60 md:hidden" @click="$emit('close-sidebar')">
                <aside class="absolute inset-y-0 left-0 w-64 z-50 bg-gradient-to-b from-alumun-guinda via-alumun-pino to-black shadow-2xl flex flex-col" @click.stop>
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
                        <button
                            class="cursor-pointer text-white/70 hover:text-white text-xs bg-black/20 hover:bg-black/30 rounded-full p-1.5"
                            @click="$emit('close-sidebar')"
                        >
                            ✕
                        </button>
                    </div>

                    <nav class="flex-1 overflow-y-auto py-4 space-y-2">
                        <SidebarItem icon="🏠" label="Inicio" :active="route().current('dashboard')" @click="$emit('navigate', 'dashboard'); $emit('close-sidebar')" />
                        <SidebarItem icon="💡" label="Reportes" />
                        <SidebarItem icon="📊" label="Tableros" />
                        <SidebarItem icon="⚙️" label="Configuración" />
                    </nav>

                    <div class="border-t border-white/10 px-3 py-3">
                        <button
                            class="cursor-pointer w-full flex items-center justify-center gap-2 text-sm bg-black/30 hover:bg-black/40 rounded-xl px-3 py-2"
                            @click="$emit('logout')"
                        >
                            <span>Salir</span>
                        </button>
                    </div>
                </aside>
            </div>
        </transition>
    </aside>
</template>



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
