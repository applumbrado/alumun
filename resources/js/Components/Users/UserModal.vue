<script setup>
import { reactive, ref, computed, watch, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import InputError from "@/Components/InputError.vue"

const page = usePage()

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  mode: { type: String, required: true }, // 'create' | 'edit'
})

const emit = defineEmits(['close'])

const tabs = [
  { id: 1, label: 'Datos Generales' },
  { id: 2, label: 'Domicilio' },
  { id: 3, label: 'Otros Datos' },
]

const roles = [
  { id: 3, label: 'Invitado' },
]

const activeTab = ref(1)
const processing = ref(false)

const toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2200,
  timerProgressBar: true,
})

const pageErrors = computed(() => page.props.errors ?? {})

const inputBase = 'w-full rounded-lg bg-black/20 border px-3 py-2 text-white placeholder:text-slate-500 focus:outline-none focus:ring-2'
const inputOk = 'border-white/10 focus:ring-emerald-500/30'
const inputErr = 'border-rose-500/60 focus:ring-rose-500/30'

const inputClass = (err) => `${inputBase} ${err ? inputErr : inputOk}`
const selectClass = (err) => `${inputBase} ${err ? inputErr : inputOk} pr-10`

// Configuración inicial del formulario
const initialFormData = computed(() => {
  const defaultData = {
    id: 0,
    username: '',
    email: '',
    password: '',

    nombre: '',
    ap_paterno: '',
    ap_materno: '',
    curp: '',
    fecha_nacimiento: '',
    genero: '1',

    emails: '',
    celulares: '',
    telefonos: '',

    user_address: {
      calle: ' ',
      num_ext: ' ',
      num_int: ' ',
      colonia: ' ',
      municipio: ' ',
      estado: ' ',
      pais: 'México',
      cp: ' ',
    },

    user_data_extend: {
      lugar_nacimiento: ' ',
      ocupacion: ' ',
      profesion: ' ',
      lugar_trabajo: ' ',
      nacionalidad: ' ',
    },

    role_id: 3,
  }

  if (props.mode === 'edit') {
    return {
      ...defaultData,
      ...props.user,
      user_address: {
        ...defaultData.user_address,
        ...(props.user.user_address || {}),
      },
      user_data_extend: {
        ...defaultData.user_data_extend,
        ...(props.user.user_data_extend || {}),
      },
    }
  }

  return defaultData
})

const formData = reactive({})

function resetForm() {
  const init = initialFormData.value

  // Asignación plana
  Object.keys(init).forEach((k) => {
    formData[k] = init[k]
  })

  // Forzar copias para objetos anidados
  formData.user_address = { ...init.user_address }
  formData.user_data_extend = { ...init.user_data_extend }

  activeTab.value = 1
}

function tabForErrors(errObj) {
  const keys = Object.keys(errObj || {})
  if (!keys.length) return 1

  const hasPrefix = (prefix) => keys.some((k) => String(k).startsWith(prefix))
  const hasAny = (arr) => keys.some((k) => arr.includes(k))

  // Tab 2: domicilio
  if (hasPrefix('user_address.')) return 2

  // Tab 3: extendidos + contactos
  if (hasPrefix('user_data_extend.') || hasAny(['emails', 'celulares', 'telefonos'])) return 3

  // Tab 1: generales
  return 1
}

watch(
  () => [props.user, props.mode],
  () => {
    resetForm()
  },
  { deep: true, immediate: true }
)

watch(
  () => pageErrors.value,
  (newErrors) => {
    nextTick(() => {
      activeTab.value = tabForErrors(newErrors)
    })
  }
)

const modalTitle = computed(() => {
  if (props.mode === 'create') return 'Nuevo Usuario'
  const u = props.user || {}
  return `Editar Usuario: ${u.id ?? ''} - ${u.username ?? ''}`
})

const submitForm = () => {
  if (processing.value) return

  processing.value = true

  const url = props.mode === 'create'
    ? route('users.store')
    : route('users.update', props.user.id)

  const method = props.mode === 'create' ? 'post' : 'put'

  router[method](url, formData, {
    preserveScroll: true,
    onSuccess: () => {
      toast.fire({ icon: 'success', title: 'Guardado correctamente' })
        emit('close');
        closeModal()
    },
    onError: (err) => {
      // Moverte al tab que tenga el primer error
      activeTab.value = tabForErrors(err)
      toast.fire({ icon: 'error', title: 'Revisa los campos marcados' })
    },
    onFinish: () => {
      processing.value = false
    },
  })
}

const closeModal = () => {
  if (processing.value) return

  // Limpia errores de Inertia
  router.reload({ only: ['errors'] })
  emit('close')
}

const goPrev = () => {
  if (activeTab.value > 1) activeTab.value--
}

const goNext = () => {
  if (activeTab.value < tabs.length) activeTab.value++
}
</script>

<template>
  <!-- Overlay -->
  <div class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4" @click.self="closeModal">
    <!-- Modal -->
    <div class="w-full max-w-5xl rounded-2xl border border-white/10 bg-black/30 shadow-2xl overflow-hidden">

      <!-- Header -->
      <div class="p-5 border-b border-white/10 bg-black/20 flex items-start justify-between gap-4">
        <div class="min-w-0">
          <h2 class="text-lg sm:text-xl font-semibold text-white truncate">
            {{ modalTitle }}
          </h2>
          <p class="mt-1 text-xs text-slate-400">
            Paso {{ activeTab }} / {{ tabs.length }}
          </p>
        </div>

        <button
          type="button"
          class="shrink-0 inline-flex items-center justify-center h-9 w-9 rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
          @click="closeModal"
          :disabled="processing"
          aria-label="Cerrar"
        >
          ✕
        </button>
      </div>

      <!-- Tabs -->
      <div class="px-5 pt-4">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="activeTab = tab.id"
            class="px-3 py-2 rounded-lg border text-sm font-medium transition"
            :class="activeTab === tab.id
              ? 'bg-emerald-600 text-white border-emerald-500/30'
              : 'bg-white/5 text-slate-200 border-white/10 hover:bg-white/10'"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Error general -->
        <div v-if="pageErrors.error" class="mt-4 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
          {{ pageErrors.error }}
        </div>
      </div>

      <form @submit.prevent="submitForm">
        <!-- Body scroll -->
        <div class="px-5 py-5 max-h-[72vh] overflow-y-auto">

          <!-- Tab 1 -->
          <div v-show="activeTab === 1" class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-slate-200 mb-1">Usuario</label>
                  <input v-model="formData.username" type="text" :class="inputClass(pageErrors.username)" />
                  <InputError :message="pageErrors.username" class="mt-2" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-200 mb-1">Email principal</label>
                  <input v-model="formData.email" type="email" :class="inputClass(pageErrors.email)" />
                  <InputError :message="pageErrors.email" class="mt-2" />
                </div>

                <div v-if="mode === 'create'">
                  <label class="block text-sm font-medium text-slate-200 mb-1">Rol de Inicio</label>
                  <select v-model="formData.role_id" :class="selectClass(pageErrors.role_id)">
                    <option v-for="role in roles" :key="role.id" :value="role.id">
                      {{ role.label }}
                    </option>
                  </select>
                  <InputError :message="pageErrors.role_id" class="mt-2" />
                </div>
              </div>

              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Ap. Paterno</label>
                    <input v-model="formData.ap_paterno" type="text" :class="inputClass(pageErrors.ap_paterno)" />
                    <InputError :message="pageErrors.ap_paterno" class="mt-2" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Ap. Materno</label>
                    <input v-model="formData.ap_materno" type="text" :class="inputClass(pageErrors.ap_materno)" />
                    <InputError :message="pageErrors.ap_materno" class="mt-2" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Nombre(s)</label>
                    <input v-model="formData.nombre" type="text" :class="inputClass(pageErrors.nombre)" />
                    <InputError :message="pageErrors.nombre" class="mt-2" />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">CURP</label>
                    <input
                      v-model="formData.curp"
                      type="text"
                      maxlength="18"
                      class="uppercase"
                      :class="inputClass(pageErrors.curp)"
                      @input="formData.curp = $event.target.value.toUpperCase()"
                    />
                    <InputError :message="pageErrors.curp" class="mt-2" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Fecha Nacimiento</label>
                    <input v-model="formData.fecha_nacimiento" type="date" :class="inputClass(pageErrors.fecha_nacimiento)" />
                    <InputError :message="pageErrors.fecha_nacimiento" class="mt-2" />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Género</label>
                    <select v-model="formData.genero" :class="selectClass(pageErrors.genero)">
                      <option value="1">Masculino</option>
                      <option value="0">Femenino</option>
                      <option value="2">Otro</option>
                    </select>
                    <InputError :message="pageErrors.genero" class="mt-2" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 2 -->
          <div v-show="activeTab === 2" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Calle</label>
                <input v-model="formData.user_address.calle" type="text" :class="inputClass(pageErrors['user_address.calle'])" />
                <InputError :message="pageErrors['user_address.calle']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Num. Ext.</label>
                <input v-model="formData.user_address.num_ext" type="text" :class="inputClass(pageErrors['user_address.num_ext'])" />
                <InputError :message="pageErrors['user_address.num_ext']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Num. Int.</label>
                <input v-model="formData.user_address.num_int" type="text" :class="inputClass(pageErrors['user_address.num_int'])" />
                <InputError :message="pageErrors['user_address.num_int']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Colonia</label>
                <input v-model="formData.user_address.colonia" type="text" :class="inputClass(pageErrors['user_address.colonia'])" />
                <InputError :message="pageErrors['user_address.colonia']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Código Postal</label>
                <input v-model="formData.user_address.cp" type="text" :class="inputClass(pageErrors['user_address.cp'])" />
                <InputError :message="pageErrors['user_address.cp']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Municipio</label>
                <input v-model="formData.user_address.municipio" type="text" :class="inputClass(pageErrors['user_address.municipio'])" />
                <InputError :message="pageErrors['user_address.municipio']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Estado</label>
                <input v-model="formData.user_address.estado" type="text" :class="inputClass(pageErrors['user_address.estado'])" />
                <InputError :message="pageErrors['user_address.estado']" class="mt-2" />
              </div>
            </div>
          </div>

          <!-- Tab 3 -->
          <div v-show="activeTab === 3" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Lugar de Nacimiento</label>
                <input v-model="formData.user_data_extend.lugar_nacimiento" type="text" :class="inputClass(pageErrors['user_data_extend.lugar_nacimiento'])" />
                <InputError :message="pageErrors['user_data_extend.lugar_nacimiento']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Ocupación</label>
                <input v-model="formData.user_data_extend.ocupacion" type="text" :class="inputClass(pageErrors['user_data_extend.ocupacion'])" />
                <InputError :message="pageErrors['user_data_extend.ocupacion']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Profesión</label>
                <input v-model="formData.user_data_extend.profesion" type="text" :class="inputClass(pageErrors['user_data_extend.profesion'])" />
                <InputError :message="pageErrors['user_data_extend.profesion']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Lugar de Trabajo</label>
                <input v-model="formData.user_data_extend.lugar_trabajo" type="text" :class="inputClass(pageErrors['user_data_extend.lugar_trabajo'])" />
                <InputError :message="pageErrors['user_data_extend.lugar_trabajo']" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Nacionalidad</label>
                <input v-model="formData.user_data_extend.nacionalidad" type="text" :class="inputClass(pageErrors['user_data_extend.nacionalidad'])" />
                <InputError :message="pageErrors['user_data_extend.nacionalidad']" class="mt-2" />
              </div>

              <div></div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Emails</label>
                <input v-model="formData.emails" type="text" :class="inputClass(pageErrors.emails)" />
                <InputError :message="pageErrors.emails" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Celulares</label>
                <input v-model="formData.celulares" type="text" :class="inputClass(pageErrors.celulares)" />
                <InputError :message="pageErrors.celulares" class="mt-2" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Teléfonos</label>
                <input v-model="formData.telefonos" type="text" :class="inputClass(pageErrors.telefonos)" />
                <InputError :message="pageErrors.telefonos" class="mt-2" />
              </div>
            </div>
          </div>

        </div>

        <!-- Footer -->
        <div class="p-5 border-t border-white/10 bg-black/20 flex items-center justify-between gap-3">
          <button
            v-if="activeTab > 1"
            type="button"
            @click="goPrev"
            class="px-4 py-2 rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
            :disabled="processing"
          >
            Anterior
          </button>
          <div v-else></div>

          <div class="flex items-center gap-2">
            <button
              v-if="activeTab < tabs.length"
              type="button"
              @click="goNext"
              class="px-4 py-2 rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
              :disabled="processing"
            >
              Siguiente
            </button>

            <button
              type="submit"
              class="px-4 py-2 rounded-lg font-semibold border border-white/10 flex items-center justify-center gap-2"
              :class="processing ? 'bg-emerald-600/70 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
              :disabled="processing"
            >
              <svg v-if="processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
              </svg>
              <span>{{ processing ? 'Guardando...' : 'Guardar' }}</span>
            </button>

            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
              :disabled="processing"
            >
              Cancelar
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
/* Mejor contraste en dark mode para el icono del date picker */
input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(0.85);
}
</style>
