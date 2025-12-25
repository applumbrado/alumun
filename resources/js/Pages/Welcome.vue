<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Link, Head } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="Bienvenido a AluMun" />
        <section class="w-full max-w-5xl mx-auto grid md:grid-cols-2 gap-10 items-center">
            <div class="space-y-6">
                <h1 class="text-3xl md:text-4xl font-bold leading-tight">
                    <span class="text-alumun-mostaza">AluMun</span>
                    <span class="block text-slate-50">Gestión de Alumbrado Público Municipal</span>
                </h1>
                <p class="text-sm md:text-base text-slate-300">
                    Anáálisis de reportes, recibos de pago y el estado de la iluminación en tiempo real desde una interfaz moderna.
                </p>
                <div class="flex flex-wrap gap-3 pt-2">
                    <Link href="/login" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-alumun-guinda text-sm font-semibold shadow-lg shadow-alumun-guinda/40 hover:bg-alumun-guinda/90 transition">
                        Iniciar sesión
                    </Link>
                    <span class="text-xs text-slate-400 inline-flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>Sistema interno
          </span>
                </div>
            </div>

            <div class="relative">
                <div class="relative rounded-3xl card p-6 backdrop-blur">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-xs text-slate-400">Estado general</p>
                            <p class="text-sm font-semibold text-slate-50">Alumbrado del municipio</p>
                        </div>
                        <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-500/40">Operativo</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="rounded-2xl bg-gradient-to-br from-alumun-guinda/80 to-black/80 p-3">
                            <p class="text-slate-200">Servicios activos</p>
                            <p class="mt-1 text-lg font-bold text-white">1000+</p>
                            <p class="mt-1 text-[10px] text-emerald-200">+23 en 24h</p>
                        </div>
                        <div class="rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 p-3">
                            <p class="text-slate-200">Grupos abiertos</p>
                            <p class="mt-1 text-lg font-bold text-alumun-mostaza">3</p>
                            <p class="mt-1 text-[10px] text-slate-300">Prioritarios</p>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-6 -right-3 w-32 h-32 bg-alumun-mostaza/20 rounded-full blur-3xl"></div>
                <div class="absolute -top-4 -left-6 w-28 h-28 bg-alumun-guinda/30 rounded-full blur-3xl"></div>
            </div>
        </section>
    </GuestLayout>
</template>
