<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ username: '', password: '', remember: false });
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />
        <section class="w-full max-w-md mx-auto">
            <div class="card p-6 md:p-8 shadow-2xl shadow-black/60 backdrop-blur-xl">
                <h1 class="text-xl font-semibold text-slate-50 mb-1">Inicia sesión</h1>
                <p class="text-xs text-slate-400 mb-6">Acceso para personal autorizado.</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="username" class="block text-xs font-medium text-slate-200 mb-1.5">Usuario</label>
                        <input id="username" v-model="form.username" type="text" class="block w-full rounded-xl border-0 bg-slate-800/80 text-slate-50 text-xs px-3 py-2.5 focus:ring-2 focus:ring-alumun-mostaza" placeholder="usuario" />
                        <p v-if="form.errors.username" class="mt-1 text-[11px] text-red-400">{{ form.errors.username }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-medium text-slate-200 mb-1.5">Contraseña</label>
                        <input id="password" v-model="form.password" type="password" class="block w-full rounded-xl border-0 bg-slate-800/80 text-slate-50 text-xs px-3 py-2.5 focus:ring-2 focus:ring-alumun-mostaza" placeholder="••••••••" />
                        <p v-if="form.errors.password" class="mt-1 text-[11px] text-red-400">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <label class="inline-flex items-center gap-2 text-[11px] text-slate-300">
                            <input type="checkbox" v-model="form.remember" class="rounded bg-slate-800 text-alumun-mostaza focus:ring-alumun-mostaza"/>
                            Recordar equipo
                        </label>
                        <span class="text-[10px] text-slate-500">¿Problemas? Contacta a sistemas.</span>
                    </div>

                    <p v-if="form.hasErrors && !form.errors.password && form.errors.username" class="text-[11px] text-red-400 bg-red-950/40 border border-red-700/50 rounded-lg px-3 py-2">
                        {{ form.errors.username }}
                    </p>

                    <button type="submit" :disabled="form.processing" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-alumun-guinda to-alumun-mostaza text-xs font-semibold shadow-lg hover:from-alumun-guinda/90 hover:to-alumun-mostaza/90 disabled:opacity-60">
                        <span v-if="!form.processing">Entrar</span>
                        <span v-else>Validando...</span>
                    </button>
                </form>
            </div>
        </section>
    </GuestLayout>
</template>
