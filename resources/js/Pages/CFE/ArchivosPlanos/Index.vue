<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { usePeriodoVigente } from "@/Composables/usePeriodoVigente.js";

const { periodoVigente, labelPeriodoVigente } = usePeriodoVigente()

const props = defineProps({
    archivosPlanos: { type: Array, default: () => [] },
    periodoActivo: { type: Object, default: null },
})

/**
 * ✅ Lista local (porque props no se pueden mutar).
 * Así puedes remover visualmente al borrar sin recargar.
 */
const archivosPlanosLocal = ref([...(props.archivosPlanos || [])])
watch(() => props.archivosPlanos, (v) => {
    archivosPlanosLocal.value = [...(v || [])]
}, { deep: true })

const files = ref([])
const uploading = ref(false)
const progress = ref(0)

// ✅ eliminar
const deletingId = ref(null)

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2400,
    timerProgressBar: true,
})

// ✅ si existe el mismo consecutivo, el backend puede renombrar al siguiente disponible
const autoConsecutivo = ref(true)

const periodoActivoYYYYMM = computed(() => {
    const ano = periodoVigente?.value?.ano ?? null
    const mes = periodoVigente?.value?.mes ?? null
    if (!ano || !mes) return ''
    return `${String(ano)}${String(mes).padStart(2, '0')}`
})

const MAX_FILES = 3

const normalize = (s='') => String(s).trim()

const parseFilename = (name) => {
    const n = normalize(name)
    // 202509_M0MO_1.xlsx
    const m = n.match(/^(\d{6})_([A-Za-z0-9]+)_(\d+)\.xlsx$/i)
    if (!m) {
        return {
            ok: false,
            error: 'Formato inválido. Usa: AAAAMM_GRUPO_CONSECUTIVO.xlsx'
        }
    }

    const periodo = m[1]
    const grupo = m[2]
    const consecutivo = parseInt(m[3], 10)

    if (!consecutivo || consecutivo < 1) {
        return { ok: false, error: 'El consecutivo debe iniciar en 1 (ej: _1.xlsx)' }
    }

    // ✅ Validar contra periodo vigente
    if (periodoActivoYYYYMM.value && periodo !== periodoActivoYYYYMM.value) {
        return {
            ok: false,
            error: `Periodo no coincide con el activo (${periodoActivoYYYYMM.value}).`
        }
    }

    return { ok: true, periodo, grupo, consecutivo }
}

const enrichedFiles = computed(() => {
    const seen = new Set()
    return (files.value ?? []).map((f) => {
        const meta = parseFilename(f.name)

        // evitar duplicados en selección (mismo nombre)
        const dup = seen.has(f.name)
        seen.add(f.name)

        return {
            file: f,
            name: f.name,
            size: f.size,
            meta,
            dup,
        }
    })
})

const hasInvalid = computed(() =>
    enrichedFiles.value.some(x => !x.meta.ok || x.dup)
)

const handlePick = (e) => {
    const picked = Array.from(e.target.files || [])
    if (!picked.length) return

    const next = [...files.value, ...picked].slice(0, MAX_FILES)
    files.value = next

    // reset input para poder volver a elegir el mismo archivo si lo quitaron
    e.target.value = ''
}

const removeFile = (name) => {
    files.value = files.value.filter(f => f.name !== name)
}

const clearAll = () => {
    files.value = []
}

const prettyBytes = (bytes) => {
    if (bytes === null || bytes === undefined) return ''
    const units = ['B','KB','MB','GB']
    let i = 0
    let n = Number(bytes)
    if (Number.isNaN(n)) return String(bytes)
    while (n >= 1024 && i < units.length - 1) {
        n /= 1024
        i++
    }
    return `${n.toFixed(i === 0 ? 0 : 1)} ${units[i]}`
}

const upload = async () => {
    if (!files.value.length) {
        return Swal.fire({ icon: 'warning', title: 'Selecciona hasta 3 archivos .XLSX' })
    }

    if (files.value.length > MAX_FILES) {
        return Swal.fire({ icon: 'warning', title: `Máximo ${MAX_FILES} archivos` })
    }

    if (!periodoActivoYYYYMM.value) {
        return Swal.fire({ icon: 'warning', title: 'No hay periodo vigente cargado' })
    }

    if (hasInvalid.value) {
        return Swal.fire({
            icon: 'warning',
            title: 'Hay archivos inválidos',
            text: 'Corrige el nombre/periodo o quita duplicados.',
        })
    }

    const confirm = await Swal.fire({
        title: '¿Subir Archivos Planos?',
        html: `
          <div style="text-align:left">
            <div><b>Periodo vigente:</b> ${periodoActivoYYYYMM.value}</div>
            <div><b>Archivos:</b> ${files.value.length}</div>
            <div style="margin-top:8px;opacity:.9">
              Si un consecutivo ya existe, ${autoConsecutivo.value ? 'se renombrará al siguiente disponible.' : 'se rechazará.'}
            </div>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, subir',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
    })
    if (!confirm.isConfirmed) return

    uploading.value = true
    progress.value = 0

    try {
        const fd = new FormData()
        files.value.forEach(f => fd.append('archivos[]', f))
        fd.append('auto_consecutivo', autoConsecutivo.value ? '1' : '0')

        const { data } = await axios.post(route('cfe.archivos-planos.upload'), fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress(ev) {
                const pct = Math.round((ev.loaded * 100) / (ev.total || 1))
                progress.value = Math.min(100, Math.max(0, pct))
            }
        })

        toast.fire({ icon: 'success', title: 'Carga completada' })

        Swal.fire({
            icon: 'success',
            title: 'Archivos procesados',
            html: `
              <div style="text-align:left">
                ${(data.procesados || []).map(x =>
                `<div>• <b>${x.original}</b> → <span>${x.saved_as || '—'}</span> <span style="opacity:.8">(${x.status})</span></div>`
            ).join('')}
              </div>
            `,
        })

        clearAll()

        // ✅ opcional: refrescar listado desde backend (si tienes endpoint)
        // Si no tienes endpoint, al menos recarga página:
        window.location.reload()

    } catch (err) {
        const msg = err?.response?.data?.message || err.message || 'Error desconocido'
        console.error(err)
        Swal.fire({ icon: 'error', title: 'Error al subir', text: msg })
    } finally {
        uploading.value = false
        progress.value = 0
    }
}

/**
 * ✅ ELIMINAR: borra DB + archivo en storage (lo hace el backend)
 */
const eliminarArchivoPlano = async (a) => {
    const confirm = await Swal.fire({
        title: '¿Eliminar archivo plano?',
        html: `
          <div style="text-align:left">
            <div><b>Archivo:</b> <code>${a.stored_name ?? ''}</code></div>
            <div><b>Grupo:</b> ${a.grupo_codigo ?? ''} <b>#</b>${a.consecutivo ?? ''}</div>
            <div style="margin-top:8px; opacity:.85">
              Esto eliminará el registro en la base de datos y el archivo del storage.
            </div>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
    })

    if (!confirm.isConfirmed) return

    deletingId.value = a.id
    try {
        const { data } = await axios.delete(route('cfe.archivos-planos.destroy', a.id))

        if (!data?.ok) throw new Error(data?.message || 'No se pudo eliminar')

        // quitar del listado local
        archivosPlanosLocal.value = archivosPlanosLocal.value.filter(x => x.id !== a.id)

        toast.fire({ icon: 'success', title: 'Archivo eliminado' })

        if (data?.file_deleted === false) {
            toast.fire({ icon: 'info', title: 'Registro eliminado, pero el archivo no existía en storage' })
        }
    } catch (err) {
        const msg = err?.response?.data?.message || err.message || 'Error desconocido'
        console.error(err)
        Swal.fire({ icon: 'error', title: 'Error al eliminar', text: msg })
    } finally {
        deletingId.value = null
    }
}
</script>

<template>
    <Head title="Archivos Planos" />
    <AuthenticatedLayout>
        <template #title>Archivos Planos</template>

        <div class="p-6 rounded-xl border border-white/10 bg-black/30 shadow">
            <div class="flex flex-col gap-1 mb-6">
                <h1 class="text-xl font-bold text-white">Archivos Planos</h1>
                <p class="text-sm text-slate-300">
                    Sube hasta <b>3</b> archivos <b>.XLSX</b> con nombre:
                    <span class="font-mono text-xs text-emerald-200">AAAAMM_GRUPO_CONSECUTIVO.xlsx</span>
                    <span class="block text-xs text-white/70 mt-1">
                        Ejemplo: <span class="font-mono">202509_M0MO_1.xlsx</span>
                    </span>
                </p>

                <div class="mt-2 inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-xl border border-white/10 bg-black/20 text-white/80">
                    <span class="opacity-80">Periodo vigente:</span>
                    <span class="font-semibold text-emerald-200">{{ labelPeriodoVigente }}</span>
                    <span class="opacity-60">({{ periodoActivoYYYYMM }})</span>
                </div>
            </div>

            <!-- Switch auto consecutivo -->
            <div class="flex items-center justify-between gap-3 mb-4 rounded-xl border border-white/10 bg-black/20 p-4">
                <div class="text-sm text-slate-200">
                    <div class="font-semibold">Resolver consecutivo automáticamente</div>
                    <div class="text-xs text-slate-400">
                        Si ya existe <span class="font-mono">AAAAMM_GRUPO_N.xlsx</span>, guardar como <span class="font-mono">..._(N+1).xlsx</span>
                    </div>
                </div>

                <button
                    type="button"
                    class="relative inline-flex h-7 w-12 items-center rounded-full transition border border-white/10"
                    :class="autoConsecutivo ? 'bg-emerald-600/70' : 'bg-white/10'"
                    @click="autoConsecutivo = !autoConsecutivo"
                >
                    <span
                        class="inline-block h-6 w-6 transform rounded-full bg-white shadow transition"
                        :class="autoConsecutivo ? 'translate-x-5' : 'translate-x-1'"
                    />
                </button>
            </div>

            <!-- Picker -->
            <div class="rounded-xl border border-dashed border-white/10 bg-black/10 p-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-white">Seleccionar archivos</div>
                        <div class="text-xs text-slate-400">
                            Máximo {{ MAX_FILES }} archivos · Solo <span class="font-mono">.xlsx</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="cursor-pointer px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold border border-white/10">
                            Elegir .XLSX
                            <input class="hidden" type="file" multiple accept=".xlsx" @change="handlePick" />
                        </label>

                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white text-sm border border-white/10"
                            @click="clearAll"
                            :disabled="!files.length"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>

                <!-- Lista -->
                <div v-if="enrichedFiles.length" class="mt-4 space-y-2">
                    <div
                        v-for="row in enrichedFiles"
                        :key="row.name"
                        class="flex items-start justify-between gap-3 rounded-xl border border-white/10 bg-black/20 p-3"
                    >
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-white font-mono text-xs truncate max-w-[22rem]">
                                    {{ row.name }}
                                </span>

                                <span v-if="row.dup" class="text-[10px] px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-200 border border-rose-400/20">
                                    duplicado
                                </span>

                                <span v-else-if="!row.meta.ok" class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-200 border border-amber-400/20">
                                    inválido
                                </span>

                                <span v-else class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-200 border border-emerald-400/20">
                                    OK
                                </span>
                            </div>

                            <div class="text-xs text-slate-400 mt-1">
                                Tamaño: {{ prettyBytes(row.size) }}
                            </div>

                            <div v-if="row.meta.ok" class="text-xs text-slate-300 mt-1">
                                Periodo: <span class="font-mono text-emerald-200">{{ row.meta.periodo }}</span> ·
                                Grupo: <span class="font-mono">{{ row.meta.grupo }}</span> ·
                                Consecutivo: <span class="font-mono">{{ row.meta.consecutivo }}</span>
                            </div>

                            <div v-else class="text-xs text-amber-200 mt-1">
                                {{ row.meta.error }}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 px-3 py-1.5 text-xs rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-white"
                            @click="removeFile(row.name)"
                        >
                            Quitar
                        </button>
                    </div>

                    <div v-if="hasInvalid" class="mt-2 text-xs text-amber-200">
                        Corrige archivos inválidos/duplicados antes de subir.
                    </div>
                </div>

                <div v-else class="mt-5 text-sm text-slate-400 text-center">
                    No has seleccionado archivos.
                </div>
            </div>

            <!-- Progreso -->
            <div v-if="uploading" class="mt-5">
                <div class="flex items-center justify-between text-xs text-slate-300 mb-2">
                    <span>Subiendo...</span>
                    <span>{{ progress }}%</span>
                </div>
                <div class="w-full h-3 bg-gray-700 rounded overflow-hidden">
                    <div class="h-3 bg-emerald-500 transition-all duration-200" :style="{ width: progress + '%' }"></div>
                </div>
            </div>

            <!-- Acción -->
            <div class="mt-6 flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="px-5 py-2 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-white font-semibold"
                    @click="clearAll"
                    :disabled="uploading || !files.length"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    class="px-5 py-2 rounded-xl border border-white/10 font-semibold flex items-center gap-2"
                    :class="(!uploading && files.length && !hasInvalid)
                        ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                        : 'bg-white/5 text-slate-500 cursor-not-allowed'"
                    :disabled="uploading || !files.length || hasInvalid"
                    @click="upload"
                >
                    <svg v-if="uploading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                    </svg>
                    <span>{{ uploading ? 'Subiendo...' : 'Subir archivos' }}</span>
                </button>
            </div>

            <!-- ✅ Listado de DB -->
            <div v-if="archivosPlanosLocal.length" class="mt-8 rounded-xl border border-white/10 bg-black/20 overflow-hidden">
                <div class="p-4 border-b border-white/10">
                    <h3 class="text-sm font-semibold text-white">Archivos del periodo</h3>
                    <p class="text-xs text-slate-400">
                        {{ archivosPlanosLocal.length }} registrados
                    </p>
                </div>

                <div class="p-4 overflow-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                        <tr class="text-slate-300 border-b border-white/10">
                            <th class="py-2">Grupo</th>
                            <th class="py-2">#</th>
                            <th class="py-2">Archivo</th>
                            <th class="py-2 text-right">Tamaño</th>
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Acción</th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr v-for="a in archivosPlanosLocal" :key="a.id" class="border-b border-white/5">
                            <td class="py-2 text-white font-mono">{{ a.grupo_codigo }}</td>
                            <td class="py-2 text-slate-200 font-mono">{{ a.consecutivo }}</td>
                            <td class="py-2 text-slate-200 font-mono">{{ a.stored_name }}</td>
                            <td class="py-2 text-right text-slate-300">{{ prettyBytes(a.size) }}</td>
                            <td class="py-2 text-slate-400 text-xs">{{ a.created_at }}</td>
                            <td class="py-2">
                                <div class="flex items-center gap-3">
                                    <a
                                        v-if="a.url"
                                        :href="a.url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-emerald-300 hover:text-emerald-200 underline"
                                    >
                                        Descargar
                                    </a>
                                    <span v-else class="text-slate-500 text-xs italic">Sin URL</span>

                                    <!-- ✅ Eliminar -->
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                                        :class="deletingId === a.id
                                            ? 'bg-white/5 text-slate-500 border-white/10 cursor-not-allowed'
                                            : 'bg-rose-600/20 text-rose-200 border-rose-400/20 hover:bg-rose-600/30'"
                                        :disabled="deletingId === a.id"
                                        @click="eliminarArchivoPlano(a)"
                                    >
                                        <svg v-if="deletingId === a.id" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                                        </svg>
                                        <span>{{ deletingId === a.id ? 'Eliminando...' : 'Eliminar' }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="mt-8 text-sm text-slate-400 text-center">
                No hay archivos planos registrados para el periodo vigente.
            </div>

        </div>
    </AuthenticatedLayout>
</template>
