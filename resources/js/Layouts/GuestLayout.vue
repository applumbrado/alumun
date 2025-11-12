<template>
    <div class="min-h-screen bg-gradient-to-br from-alumun-fondo via-slate-900 to-black text-white flex flex-col">
        <header class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-9 w-9 rounded-xl bg-alumun-guinda flex items-center justify-center shadow-lg shadow-alumun-guinda/50">
                    <span class="font-bold text-sm">AP</span>
                </div>
                <div class="leading-tight">
                    <span class="font-semibold tracking-wide">AluMun</span>
                    <span class="block text-xs text-slate-400">Alumbrado Público Municipal</span>
                </div>
            </div>
            <slot name="header-actions"></slot>
        </header>

        <main class="flex-1 flex items-center justify-center px-4 py-8">
            <slot />
        </main>

        <footer class="text-center text-xs text-slate-500 py-4">
            © {{ new Date().getFullYear() }} AluMun · Alumbrado Público Municipal
        </footer>
    </div>
</template>
<script setup></script>
