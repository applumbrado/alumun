<script setup>
import { ref } from 'vue'
import Swal from 'sweetalert2'
import JSZip from 'jszip'

const props = defineProps({
    label: { type: String, default: 'Arrastra tus ZIP de CFE aquí' },
    sublabel: { type: String, default: 'o clic para seleccionar' },
    maxSizeMB: { type: Number, default: 50 },
    maxFiles: { type: Number, default: 30 },
})

const emit = defineEmits(['files'])

const dragging = ref(false)
const archivos = ref([])   // { file, xmlCount, totalXmlSize, invalidXml, preview }
const fileInput = ref(null)

/* =============================================================
   PROCESADOR GENERAL DE ARCHIVOS ZIP
============================================================= */
async function procesarArchivos(files) {
    const onlyZip = files.filter(f => f.name.toLowerCase().endsWith('.zip'))

    if (!onlyZip.length) return alerta('Archivo inválido', 'Solo se permiten ZIP.')

    // Validar cantidad total
    if (archivos.value.length + onlyZip.length > props.maxFiles) {
        return alerta('Límite excedido', `Máximo permitido: ${props.maxFiles} archivos ZIP.`)
    }

    for (const file of onlyZip) {
        const validation = validarPeso(file)
        if (!validation.ok) return alerta('Archivo demasiado grande', validation.msg)

        // Analizar contenido del ZIP
        const info = await analizarZip(file)

        if (!info.valid) {
            alerta('ZIP inválido', `${file.name} no contiene XML válidos.`)
            continue
        }

        archivos.value.push({
            file,
            xmlCount: info.xmlCount,
            invalidXml: info.invalidXml,
            totalXmlSize: info.totalXmlSize,
            preview: info.preview
        })
    }

    emit('files', archivos.value.map(a => a.file))
}

/* =============================================================
   VALIDACIÓN AVANZADA DE PESO
============================================================= */
function validarPeso(file) {
    const limit = props.maxSizeMB * 1024 * 1024
    if (file.size > limit) {
        return {
            ok: false,
            msg: `${file.name} supera los ${props.maxSizeMB} MB`
        }
    }
    return { ok: true }
}

/* =============================================================
   ANALIZAR ZIP — powered by JSZip
   Obtiene:
   - número de XML
   - tamaño total
   - xml inválidos
   - preview de primera línea
============================================================= */
async function analizarZip(file) {
    try {
        const zip = await JSZip.loadAsync(file)

        let xmlCount = 0
        let totalXmlSize = 0
        let invalidXml = 0
        let preview = null

        for (const path in zip.files) {
            if (path.toLowerCase().endsWith('.xml')) {
                xmlCount++

                const xml = zip.files[path]
                const text = await xml.async('string')

                totalXmlSize += xml._data.uncompressedSize ?? text.length

                // Validar XML mínimamente
                if (!text.trim().startsWith('<')) {
                    invalidXml++
                    continue
                }

                // Primera vista previa
                if (!preview) {
                    preview = text.substring(0, 120) + '...'
                }
            }
        }

        return {
            valid: xmlCount > 0,
            xmlCount,
            totalXmlSize,
            invalidXml,
            preview,
        }
    } catch (e) {
        return {
            valid: false,
            xmlCount: 0,
            totalXmlSize: 0,
            invalidXml: 0,
            preview: null,
        }
    }
}

/* =============================================================
   DROP / CLICK HANDLERS
============================================================= */
function handleDrop(e) {
    dragging.value = false

    const items = [...e.dataTransfer.items]
    const files = []

    for (const item of items) {
        if (item.kind === 'file') {
            const entry = item.webkitGetAsEntry()
            if (entry.isFile) {
                files.push(item.getAsFile())
            }
        }
    }

    procesarArchivos(files)
}

function handleFiles(e) {
    procesarArchivos([...e.target.files])
}

function openDialog() {
    fileInput.value.click()
}

/* =============================================================
   UTILIDAD: ALERTA RÁPIDA
============================================================= */
function alerta(title, text) {
    Swal.fire({ icon: 'error', title, text })
}

/* =============================================================
   ELIMINAR ARCHIVO
============================================================= */
function removeFile(name) {
    archivos.value = archivos.value.filter(a => a.file.name !== name)
    emit('files', archivos.value.map(a => a.file))
}

/* =============================================================
   LIMPIAR TODOS
============================================================= */
function clearAll() {
    archivos.value = []
    emit('files', [])
}
</script>

<template>
    <!-- DROPZONE SUPER PREMIUM -->
    <div
        class="p-6 rounded-2xl border-2 border-dashed bg-black/40 cursor-pointer transition
               backdrop-blur-md relative overflow-hidden group"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="handleDrop"
        @click="openDialog"
        :class="{
            'border-alumun-guinda shadow-[0_0_30px_rgba(181,23,52,0.7)] bg-black/60': dragging,
            'hover:border-alumun-guinda hover:bg-black/60': !dragging
        }"
    >
        <!-- Glow radial al arrastrar -->
        <div
            v-if="dragging"
            class="absolute inset-0 bg-gradient-radial from-alumun-guinda/30 to-transparent animate-pulse pointer-events-none"
        ></div>

        <!-- Contenido del Dropzone -->
        <div class="relative z-10 flex flex-col items-center">
            <svg class="w-14 h-14 text-white/70 mb-3 group-hover:text-alumun-guinda transition"
                 fill="none" stroke="currentColor" stroke-width="1.5"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 16V8m0 0l-3 3m3-3l3 3m2 5a4 4 0 100-8 5.5 5.5 0 10-10.9 1.5A4.5 4.5 0 007.5 20H17" />
            </svg>

            <p class="text-slate-300 text-sm">{{ props.label }}</p>
            <p class="text-slate-500 text-xs">{{ props.sublabel }}</p>
        </div>

        <!-- Input oculto -->
        <input
            ref="fileInput"
            type="file"
            class="hidden"
            accept=".zip"
            @change="handleFiles"
        />
    </div>

    <!-- LISTA AVANZADA DE ZIPs -->
    <div v-if="archivos.length" class="mt-5 space-y-3">

        <!-- Botón Limpiar todo -->
        <div class="text-right mb-2">
            <button @click="clearAll"
                    class="text-xs bg-red-700/40 hover:bg-red-700/60 transition px-2 py-1 rounded-md text-white/90">
                Limpiar todo
            </button>
        </div>

        <transition-group name="fade" tag="div">
            <div v-for="a in archivos" :key="a.file.name"
                 class="bg-black/50 border border-white/10 rounded-xl p-4 flex items-start gap-4 relative">

                <!-- Icono ZIP -->
                <div class="flex-shrink-0">
                    <svg class="w-10 h-10 text-alumun-guinda" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h8l6-6V5a2 2 0 00-2-2H4z" />
                    </svg>
                </div>

                <!-- Información -->
                <div class="flex-1 text-xs text-slate-300">
                    <div class="font-semibold text-white mb-1">{{ a.file.name }}</div>

                    <div class="text-slate-400">
                        <div>XML encontrados: <span class="text-white">{{ a.xmlCount }}</span></div>
                        <div v-if="a.invalidXml > 0" class="text-red-400">
                            XML inválidos: {{ a.invalidXml }}
                        </div>
                        <div>Tamaño total XML: {{ (a.totalXmlSize / 1024).toFixed(1) }} KB</div>
                    </div>

                    <!-- Preview -->
                    <div v-if="a.preview" class="mt-2 p-2 bg-black/30 rounded-md text-[10px] text-slate-400">
                        {{ a.preview }}
                    </div>

                </div>

                <!-- Botón eliminar ZIP -->
                <button
                    @click="removeFile(a.file.name)"
                    class="absolute top-3 right-3 text-red-400 hover:text-red-200 text-xs">
                    ✕
                </button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: all 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
