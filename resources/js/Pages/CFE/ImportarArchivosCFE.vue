<script setup>
import { ref } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

import DataTableCustom from "@/Components/Ui/DataTableCustom.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import ActionButtons from "@/Components/Ui/ActionButtons.vue";
import ZipDropzone from "@/Components/ZipDropzone.vue";
import {usePeriodoVigente} from "@/Composables/usePeriodoVigente.js";

const archivosZip = ref([])
const progreso = ref(0)           // progreso global (0–100)
const progresoPorArchivo = ref({}) // progreso por archivo individual
const cargando = ref(false)
const forceOverwrite = ref(false)       // ✔ checkbox sobrescribir
const files = ref([])
const resultados = ref([])              // ✔ aquí guardamos lo que regresa backend

const { labelPeriodoVigente } = usePeriodoVigente()

// Recibos enviados por Laravel (por ejemplo usando Inertia::render)
const props = defineProps({
    recibos: { type: Array, default: () => [] }
})

const isSumaryFooter = ref(true)

function handleZipFiles(files) {
    archivosZip.value = files
}

// Nunca mutamos props → usamos estado local
const recibosLocal = ref([...props.recibos])

const showSelection = ref(false);
const isColActions = ref(true);

const MAX_FILES_PER_REQUEST = 5


// 🎨 Paleta de estilos (se recicla si hay más mensajes que colores)
const MSG_PALETTE = [
    'bg-emerald-500/15 text-emerald-200 border-emerald-400/30',
    'bg-sky-500/15 text-sky-200 border-sky-400/30',
    'bg-violet-500/15 text-violet-200 border-violet-400/30',
    'bg-amber-500/15 text-amber-200 border-amber-400/30',
    'bg-fuchsia-500/15 text-fuchsia-200 border-fuchsia-400/30',
    'bg-teal-500/15 text-teal-200 border-teal-400/30',
    'bg-lime-500/15 text-lime-200 border-lime-400/30',
    'bg-orange-500/15 text-orange-200 border-orange-400/30',
    'bg-cyan-500/15 text-cyan-200 border-cyan-400/30',
    'bg-rose-500/15 text-rose-200 border-rose-400/30',
]

// Mapa reactivo: "mensaje" => "clases tailwind"
const msgStyleMap = ref({})

function getMessageText(item) {
    return item?.msg || item?.mensaje || item?.error || item?.skipped || 'Sin mensaje'
}

function getMsgClasses(item) {
    const key = getMessageText(item)

    // si aún no existe asignación, asigna el siguiente color
    if (!msgStyleMap.value[key]) {
        const used = Object.keys(msgStyleMap.value).length
        msgStyleMap.value[key] = MSG_PALETTE[used % MSG_PALETTE.length]
    }

    return msgStyleMap.value[key]
}

function getStatusMeta(item) {
    const st = item?.status || (item?.success ? 'ok' : 'error')

    switch (st) {
        case 'created':
            return { label: 'Creado', cls: 'bg-emerald-700 text-white', icon: '✔' }
        case 'updated':
            return { label: 'Actualizado', cls: 'bg-sky-700 text-white', icon: '↻' }
        case 'skipped':
            return { label: 'Omitido', cls: 'bg-amber-300 text-gray-900', icon: '⏭' }
        default:
            if (item?.success) return { label: 'OK', cls: 'bg-green-700 text-white', icon: '✔' }
            return { label: 'Error', cls: 'bg-red-700 text-white', icon: '✖' }
    }
}

function getOcr(item) {
    const o = item?.ocr ?? {}
    return {
        aamm: item?.OCR_AAMM ?? o.OCR_AAMM ?? null,
        aaaa: item?.OCR_AAAA ?? o.OCR_AAAA ?? null,
        mm: item?.OCR_MM ?? o.OCR_MM ?? null,
        mmNom: item?.OCR_MM_NOM ?? o.OCR_MM_NOM ?? null,
        tipo: item?.OCR_TIPO ?? o.OCR_TIPO ?? null,
        digito: item?.OCR_DIGITO ?? o.OCR_DIGITO ?? null,
    }
}

function getPeriodoActivo(item) {
    const p = item?.periodo_activo ?? {}
    return {
        aaaa: item?.anio_activo ?? p.ano ?? null,
        mm: item?.mes_activo ?? p.mes ?? null,
    }
}

function hasOcr(item) {
    const o = getOcr(item)
    return !!(o.aaaa || o.mm || o.aamm || o.tipo || o.digito)
}

function pad2(v) {
    if (v === null || v === undefined || v === '') return ''
    return String(v).padStart(2, '0')
}


async function subirArchivo() {
    if (!archivosZip.value.length) {
        return Swal.fire({
            icon: 'warning',
            title: 'Selecciona al menos un archivo ZIP',
        })
    }

    const confirm = await Swal.fire({
        title: '¿Procesar archivos CFE?',
        html: `
            Se leerán los XML dentro de los ZIP.<br/>
            Se extraerán RPU, periodos, totales, tarifa, dirección.<br/><br/>
            <strong>¿Deseas continuar?</strong>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
    })

    if (!confirm.isConfirmed) return

    cargando.value = true
    resultados.value = []
    progresoPorArchivo.value = {}

    try {


        const totalFiles = archivosZip.value.length

        for (let i = 0; i < totalFiles; i += MAX_FILES_PER_REQUEST) {
            const batch = archivosZip.value.slice(i, i + MAX_FILES_PER_REQUEST)
            const formData = new FormData()

            batch.forEach(file => {
                formData.append('archivos[]', file)
            })

            formData.append('forceOverwrite', forceOverwrite.value ? 1 : 0)

            const { data } = await axios.post(route('cfe.importar'), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress(progressEvent) {
                    const porcentaje = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    )
                    progreso.value = porcentaje

                    const totalSize = batch.reduce((a, f) => a + f.size, 0)
                    let cargado = progressEvent.loaded

                    batch.forEach(file => {
                        const porcentajeEstimado = Math.min(
                            100,
                            Math.round((cargado / totalSize) * 100)
                        )
                        progresoPorArchivo.value[file.name] = porcentajeEstimado
                    })
                }
            })

            // acumular resultados de todos los lotes
            if (Array.isArray(data.procesados)) {
                resultados.value.push(...data.procesados)
            }

            // si también traes recibos:
            if (Array.isArray(data.recibos)) {
                recibosLocal.value = data.recibos
            }
        }

        Swal.fire({
            icon: 'success',
            title: 'Importación completa',
            timer: 2000
        })

    } catch (err) {
        console.error(err.message)
        Swal.fire({
            icon: 'error',
            title: 'Error al importar \n\t' + err.message
        })
    } finally {
        cargando.value = false
        progreso.value = 0
    }
}



</script>


<template>
    <AuthenticatedLayout>
        <Head title="Importar recibos" />
        <template #title>Panel general de alumbrado</template>

        <div class="p-6 rounded shadow">

            <h1 class="text-xl font-bold mb-4">
                Importar archivos de CFE – {{ labelPeriodoVigente }}
            </h1>

            <!-- DROP ZIP -->

            <ZipDropzone
                label="Sube tus ZIP de CFE"
                sublabel="o arrástralos aquí"
                @files="handleZipFiles"
            />

            <!-- Switch sobrescribir -->
            <div class="flex items-center justify-between gap-4 mb-4 p-3 rounded-lg border border-white/10 bg-black/20">
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-white">
                        Sobrescribir registros existentes
                    </span>
                                <span class="text-xs text-slate-400">
                        Si está apagado, los duplicados se omiten y solo se reparan archivos faltantes.
                    </span>
                </div>

                <button
                    type="button"
                    role="switch"
                    :aria-checked="forceOverwrite"
                    @click="forceOverwrite = !forceOverwrite"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-black"
                    :class="forceOverwrite ? 'bg-emerald-500/80' : 'bg-slate-600/60'"
                >
                    <span
                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-200"
                        :class="forceOverwrite ? 'translate-x-5' : 'translate-x-1'"
                    />
                </button>
            </div>

            <!-- Botón subir -->
            <button
                @click="subirArchivo"
                class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700"
                :disabled="cargando"
            >
                <span v-if="!cargando">Importar ZIP</span>
                <span v-else>Procesando...</span>
            </button>

            <!-- Loader -->
            <div v-if="cargando" class="mt-4 text-blue-600 font-semibold">
                Procesando ZIP...
            </div>


            <!-- Barra de progreso global -->
            <div v-if="progreso > 0" class="mt-6">
                <p class="text-white text-sm mb-1">Subiendo archivos... {{ progreso }}%</p>

                <div class="w-full h-3 bg-gray-700 rounded overflow-hidden">
                    <div class="h-3 bg-green-500 transition-all duration-200"
                         :style="{ width: progreso + '%' }"></div>
                </div>
            </div>

            <!-- =======================================
                 RESULTADOS DEL PROCESO
            ======================================= -->
            <div v-if="resultados.length" class="mt-8 space-y-4">

                <h2 class="text-xl font-bold text-white">Resultados de Importación</h2>

                <div v-for="zip in resultados" :key="zip.zip"
                     class="border border-white/10 rounded-lg bg-black/30 p-4">

                    <!-- Encabezado ZIP -->
                    <div class="flex justify-between items-center cursor-pointer"
                         @click="zip.open = !zip.open">

                        <div class="flex flex-col">
                            <span class="text-lg font-semibold text-white">
                                {{ zip.zip }}
                            </span>

                                        <span class="text-sm text-slate-400">
                                {{ zip.procesados.length }} XML encontrados
                            </span>
                                    </div>

                                    <div>
                            <span v-if="zip.procesados.every(x => x.success)"
                                  class="px-3 py-1 text-sm rounded bg-green-600 text-white">
                                ✔ Completado
                            </span>

                                        <span v-else
                                              class="px-3 py-1 text-sm rounded bg-red-600 text-white">
                                ⚠ Con Errores
                            </span>
                        </div>
                    </div>

                    <!-- Contenido (acordeón) -->
                    <transition name="fade">
                        <div v-if="zip.open" class="mt-4 bg-black/20 rounded p-3">

                            <table class="w-full text-left text-sm">
                                <thead>
                                <tr class="text-slate-300 border-b border-white/10">
                                    <th class="py-2">Archivo XML</th>
                                    <th class="py-2">Estado</th>
                                    <th class="py-2">Mensaje</th>
                                </tr>
                                </thead>

                                <tbody>
                                <tr v-for="item in zip.procesados" :key="item.archivo"
                                    class="border-b border-white/5">

                                    <td class="py-2 text-white">
                                        {{ item.archivo }}
                                    </td>

                                    <td class="py-2">
                                        <span
                                            class="px-2 py-1 rounded text-xs inline-flex items-center gap-1"
                                            :class="getStatusMeta(item).cls"
                                        >
                                            <span>{{ getStatusMeta(item).icon }}</span>
                                            <span>{{ getStatusMeta(item).label }}</span>
                                        </span>
                                    </td>

                                    <td class="py-2">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded text-xs border"
                                            :class="getMsgClasses(item)"
                                        >
                                            {{ getMessageText(item) }}
                                        </span>

                                        <!-- Extra opcional: cuando venga fuera de periodo, muestra OCR -->
                                        <div v-if="item?.status === 'skipped' && hasOcr(item)" class="mt-1 text-xs text-slate-400">
                                            <span>
                                                Archivo:
                                                {{ getOcr(item).aaaa }}-{{ pad2(getOcr(item).mm) }}
                                                <template v-if="getOcr(item).tipo"> • Tipo: {{ getOcr(item).tipo }}</template>
                                                <template v-if="getOcr(item).digito"> • Dígito: {{ getOcr(item).digito }}</template>
                                            </span>

                                            <span v-if="getPeriodoActivo(item).aaaa || getPeriodoActivo(item).mm">
                                                • Activo: {{ getPeriodoActivo(item).aaaa }}-{{ pad2(getPeriodoActivo(item).mm) }}
                                            </span>
                                        </div>
                                    </td>

                                </tr>
                                </tbody>


                            </table>

                        </div>
                    </transition>
                </div>

            </div>



            <!-- TABLA (NO LA TOQUÉ, SOLO CAMBIÉ props.recibos → recibosLocal) -->
            <div v-if="recibosLocal.length > 0" class="mt-6">
                <DataTableCustom
                    title="Importar archivos recibos"
                    :items="recibosLocal"
                    :columns="[
                        { label: '🆔', field: 'id', sortable: true },
                        { label: 'RPU', field: 'rpu', sortable: true  },
                        { label: 'Periodo', field: 'periodo', sortable: true  },
                        { label: 'Consumo', field: 'consumo', sortable: true, align: 'text-right' },
                        { label: 'Subtotal', field: 'energia', sortable: true, align:'text-right'  },
                        { label: 'IVA', field: 'iva', sortable: true  },
                        { label: 'Total', field: 'total', sortable: true  },
                        { label: 'PDF', field: 'pdf_file', sortable: false  },
                        { label: 'XML', field: 'xml_file', sortable: false  },
                    ]"
                    :showSelection="showSelection"
                    :isColActions="isColActions"
                    paginationMode="items"
                    :footerSummary="true"
                    :summaryFields="['consumo','energia']"
                    summary-column="energia"
                >
                    <template #actions="{ item }">
                        <ActionButtons
                            :onEdit="() => openEdit(item)"
                            :onDelete="() => destroyItem(item)"
                        />
                    </template>
                    <!-- Celda PDF -->
                    <template #pdf_file="{ item }">
                        <a
                            v-if="item.pdf_url"
                            :href="item.pdf_url"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-rose-400 hover:text-rose-300 underline"
                        >
                            <i class="fa-regular fa-file-pdf"></i>
                            <span>PDF</span>
                        </a>
                        <span v-else class="text-slate-500 text-xs italic">Sin PDF</span>
                    </template>

                    <!-- Celda XML -->
                    <template #xml_file="{ item }">
                        <a
                            v-if="item.xml_url"
                            :href="item.xml_url"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-sky-400 hover:text-sky-300 underline"
                        >
                            <i class="fa-regular fa-file-code"></i>
                            <span>XML</span>
                        </a>
                        <span v-else class="text-slate-500 text-xs italic">Sin XML</span>
                    </template>
                </DataTableCustom>
            </div>

        </div>

    </AuthenticatedLayout>
</template>

<!-- Animación para acordeón -->
<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: all .25s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
