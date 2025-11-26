<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
const props = defineProps({ user: Object, stats: Object });
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Dashboard" />
        <template #title>Panel general de alumbrado</template>

        <div class="space-y-6">
            <section class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs text-slate-400">Bienvenido(a)</p>
                    <h2 class="text-lg md:text-xl font-semibold text-slate-50">
                        {{ props.user.nombre_completo }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Visualiza el estado del alumbrado y los reportes activos.</p>
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-2 rounded-2xl bg-alumun-pino/40 border border-emerald-500/40 text-xs text-emerald-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Monitoreo en tiempo real</span>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-4">
                <div class="card p-4"><span class="text-[11px] text-slate-400">Reportes pendientes</span><span class="text-2xl font-semibold text-alumun-mostaza">{{ props.stats?.reportes_pendientes }}</span></div>
                <div class="card p-4"><span class="text-[11px] text-slate-400">Luminarias operativas</span><span class="text-2xl font-semibold text-emerald-400">{{ props.stats?.luminarias_operativas }}</span></div>
                <div class="card p-4"><span class="text-[11px] text-slate-400">Luminarias apagadas</span><span class="text-2xl font-semibold text-red-400">{{ props.stats?.luminarias_apagadas }}</span></div>
                <div class="rounded-2xl p-4 bg-gradient-to-br from-alumun-guinda to-alumun-mostaza"><span class="text-[11px] text-slate-100/90">Zonas prioritarias</span><span class="text-2xl font-semibold text-slate-50">{{ props.stats?.zonas_prioritarias }}</span></div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
