<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import UserModal from '@/Components/Users/UserModal.vue'
import DownloadButton from '@/Components/DownloadButton.vue'

import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Swal from 'sweetalert2'

import { can, reloadRolesAndPermissions } from 'laravel-permission-to-vuejs'
reloadRolesAndPermissions()

const page = usePage()

const users = computed(() => page.props.users)
const totalUsuarios = computed(() => page.props.totalUsuarios)

const props = defineProps({
    tipo_usuario: {
        type: Number,
        required: true,
    },
})

const textSearch = ref(new URLSearchParams(window.location.search).get('search') || '')

const showCreateModal = ref(false)
const selectedUser = ref(null)

const deleting = ref({}) // { [userId]: true }

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
})

const refreshData = () => {
    router.reload({
        only: ['totalUsuarios', 'users'],
        preserveScroll: true,
    })
}

const handleClose = () => {
    selectedUser.value = null
    showCreateModal.value = false
}

const handleModalSuccess = () => {
    toast.fire({ icon: 'success', title: 'Guardado correctamente' })
    refreshData()
}

const destroy = async (userId) => {
    const confirm = await Swal.fire({
        title: '¿Eliminar usuario?',
        html: `Se eliminará el usuario <strong>#${userId}</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
    })

    if (!confirm.isConfirmed) return

    deleting.value = { ...deleting.value, [userId]: true }

    router.delete(route('users.delete', userId), {
        preserveScroll: true,
        onSuccess: () => {
            toast.fire({ icon: 'success', title: 'Usuario eliminado' })
            refreshData()
        },
        onError: (err) => {
            const msg = err?.message || 'No se pudo eliminar el usuario'
            toast.fire({ icon: 'error', title: msg })
        },
        onFinish: () => {
            const { [userId]: _, ...rest } = deleting.value
            deleting.value = rest
        },
    })
}
</script>

<template>
    <Head title="Usuarios" />

    <!-- Modal de creación -->
    <UserModal
        v-if="showCreateModal"
        mode="create"
        class="z-10"
        @close="showCreateModal = false"
        @success="() => { showCreateModal = false; handleModalSuccess() }"
    />

    <!-- Modal de edición -->
    <UserModal
        v-if="selectedUser"
        :user="selectedUser"
        mode="edit"
        class="z-20"
        @close="handleClose"
        @success="() => { handleClose(); handleModalSuccess() }"
    />

    <AuthenticatedLayout>
        <template #title>
            {{ props.tipo_usuario === 0 ? 'Usuarios' : 'Alumnos' }}
        </template>

        <div class="p-6 rounded-xl border border-white/10 bg-black/30 shadow">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-white">
                        ({{ totalUsuarios }}) {{ props.tipo_usuario === 0 ? 'Usuarios' : 'Alumnos' }}
                    </h1>
                    <p class="text-sm text-slate-300">Catálogo general</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold border border-white/10 bg-emerald-600 text-white hover:bg-emerald-700 transition"
                    >
                        <i class="fa fa-user-plus"></i>
                        <span>Crear usuario</span>
                    </button>

                    <DownloadButton
                        v-show="props.tipo_usuario === 0"
                        url="/users/download/data"
                        button-text="Descargar"
                        variant="outline"
                        size="md"
                        class="!mt-0"
                    />

                    <DownloadButton
                        v-show="props.tipo_usuario === 1"
                        url="/reportes/lista-alumnos"
                        button-text="Listado"
                        variant="outline"
                        size="md"
                        class="!mt-0"
                        icon-report="fas fa-users"
                    />

                    <DownloadButton
                        v-show="props.tipo_usuario === 1 && can('modificar becas | all ')"
                        url="/reportes/becas-por-grupo-fpdf"
                        button-text="Becas"
                        variant="outline"
                        size="md"
                        class="!mt-0"
                        icon-report="fas fa-file-pdf"
                    />

                    <DownloadButton
                        v-show="props.tipo_usuario === 1 && can('modificar becas | all ')"
                        url="/reportes/becas-por-grupo/excel"
                        button-text="Becas"
                        variant="outline"
                        size="md"
                        class="!mt-0"
                        icon-report="fas fa-file-excel"
                    />
                </div>
            </div>

            <!-- Search + Pagination -->
            <div class="mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
                <form action="/users" class="w-full md:w-auto flex gap-2">
                    <input
                        v-model="textSearch"
                        type="text"
                        name="search"
                        id="search"
                        placeholder="🔍 Buscar usuario..."
                        class="w-full md:w-72 rounded-lg bg-black/20 border border-white/10 text-white placeholder:text-slate-500 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                    />

                    <button
                        type="submit"
                        :disabled="textSearch.length === 0"
                        class="h-[42px] px-4 rounded-lg font-semibold border border-white/10 transition"
                        :class="textSearch.length
                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                            : 'bg-white/5 text-slate-500 cursor-not-allowed'"
                    >
                        Buscar
                    </button>
                </form>

                <nav v-if="users?.links?.length" class="flex flex-wrap gap-1 justify-end">
                    <template v-for="(link, index) in users.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1.5 rounded-lg text-sm border border-white/10 transition"
                            :class="link.active ? 'bg-emerald-600 text-white' : 'bg-white/5 text-slate-200 hover:bg-white/10'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1.5 rounded-lg text-sm border border-white/10 bg-white/5 text-slate-500"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>

            <!-- Table -->
            <div class="overflow-hidden w-full rounded-xl border border-white/10 bg-black/20">
                <div class="overflow-x-auto w-full">
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-slate-300 uppercase border-b border-white/10 bg-black/30">
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Username</th>
                                <th class="px-4 py-3">Nombre completo</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">CURP</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/10">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="text-slate-200 hover:bg-white/5"
                            >
                                <td class="px-4 py-3 text-sm">{{ user.id }}</td>
                                <td class="px-4 py-3 text-sm">{{ user.username }}</td>
                                <td class="px-4 py-3 text-sm">{{ user.full_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ user.email }}</td>
                                <td class="px-4 py-3 text-sm">{{ user.curp }}</td>

                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="destroy(user.id)"
                                            :disabled="!!deleting[user.id]"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-white/10 transition"
                                            :class="deleting[user.id] ? 'bg-white/5 text-slate-500 cursor-not-allowed' : 'bg-white/5 text-rose-300 hover:bg-rose-500/15'"
                                            title="Eliminar"
                                        >
                                            <svg v-if="deleting[user.id]" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                                            </svg>
                                            <i v-else class="fa fa-trash"></i>
                                        </button>

                                        <button
                                            type="button"
                                            @click="selectedUser = user"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-white/10 bg-white/5 text-sky-300 hover:bg-sky-500/15 transition"
                                            title="Editar"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!users?.data?.length">
                                <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                                    No hay registros para mostrar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
