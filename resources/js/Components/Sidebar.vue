<script setup>
import SidebarItem from '@/Components/SidebarItem.vue'
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { usePeriodoVigente } from "@/Composables/usePeriodoVigente.js";
import { can, reloadRolesAndPermissions } from "laravel-permission-to-vuejs";

const emit = defineEmits([
    'toggle-collapse',
    'navigate',
    'logout',
    'close-sidebar'
])

const props = defineProps({
    isCollapsed: { type: Boolean, default: false },
    sidebarOpen: { type: Boolean, default: false },
    fullName: { type: String, default: '' },
    initials: { type: String, default: '' },
})

const { labelPeriodoVigente } = usePeriodoVigente()

// ✅ IMPORTANTE: bandera reactiva para re-render cuando terminen permisos
const permsReady = ref(false)

// ✅ Submenús (desktop + mobile)
const openCatalogos = ref(false)
const openUsuarios  = ref(false)
const openCatalogosMobile = ref(false)
const openUsuariosMobile  = ref(false)

// Ziggy safe helper
const safeCurrent = (pattern) => {
    try { return route().current(pattern) } catch { return false }
}

// ✅ Active exacto según tus routes
const isInicioActive = computed(() => safeCurrent('dashboard'))
const isGruposActive = computed(() => safeCurrent('grupos.index'))
const isServiciosActive = computed(() => safeCurrent('servicios.index'))
const isPeriodosActive = computed(() => safeCurrent('periodos.index'))
const isRecibosActive = computed(() => safeCurrent('cfe.importar.index'))
const isUploadArchivosPlanos = computed(() => safeCurrent('cfe.archivos-planos.index'))
const isConciliacionesActive = computed(() => safeCurrent('cfe.conciliaciones.index'))

const isUsersActive = computed(() => safeCurrent('users.index'))
const isRolesActive = computed(() => safeCurrent('bulk.roles.edit'))
const isPermisosActive = computed(() => safeCurrent('bulk.permisos.edit'))

const isCatalogosActive = computed(() =>
    isGruposActive.value || isServiciosActive.value || isPeriodosActive.value
)

const isUsuariosActive = computed(() =>
    isUsersActive.value || isRolesActive.value || isPermisosActive.value
)

/**
 * ✅ Items visibles
 * IMPORTANTE: solo calcular cuando permsReady = true,
 * para que Vue reactive y re-renderice al terminar reloadRolesAndPermissions()
 */
const catalogosItems = computed(() => {
    if (!permsReady.value) return []
    return ([
        { key: 'grupos',    show: can(' crear grupo | all '),    label: 'Grupos',    icon: '🏔', active: isGruposActive.value,    go: 'grupos.index' },
        { key: 'periodos',  show: can(' crear periodo | all '),  label: 'Periodos',  icon: '🚞', active: isPeriodosActive.value,  go: 'periodos.index' },
        { key: 'servicios', show: can(' crear servicio | all '), label: 'Servicios', icon: '💡', active: isServiciosActive.value, go: 'servicios.index' },
    ]).filter(x => x.show)
})

const usuariosItems = computed(() => {
    if (!permsReady.value) return []
    return ([
        { key: 'users',    show: can(' ver usuarios | all '),  label: 'Usuarios', icon: '👭', active: isUsersActive.value,    go: 'users.index' },
        { key: 'roles',    show: can(' ver roles | all '),     label: 'Roles',    icon: '🧩', active: isRolesActive.value,    go: 'bulk.roles.edit' },
        { key: 'permisos', show: can(' ver permisos | all '),  label: 'Permisos', icon: '🔐', active: isPermisosActive.value, go: 'bulk.permisos.edit' },
    ]).filter(x => x.show)
})

const catalogosCount = computed(() => catalogosItems.value.length)
const usuariosCount  = computed(() => usuariosItems.value.length)

// Regla: si hay activo, subdirectorio abierto
const syncOpenDesktop = () => {
    if (isCatalogosActive.value) { openCatalogos.value = true; openUsuarios.value = false; return }
    if (isUsuariosActive.value)  { openUsuarios.value = true;  openCatalogos.value = false; return }
    openCatalogos.value = false
    openUsuarios.value = false
}
const syncOpenMobile = () => {
    if (isCatalogosActive.value) { openCatalogosMobile.value = true; openUsuariosMobile.value = false; return }
    if (isUsuariosActive.value)  { openUsuariosMobile.value = true;  openCatalogosMobile.value = false; return }
    openCatalogosMobile.value = false
    openUsuariosMobile.value = false
}

onMounted(async () => {
    try {
        // ✅ Esperar a que carguen roles/permisos antes de pintar menú
        await Promise.resolve(reloadRolesAndPermissions())
    } finally {
        permsReady.value = true
        syncOpenDesktop()
        syncOpenMobile()
    }
})

watch([isCatalogosActive, isUsuariosActive], () => {
    syncOpenDesktop()
    syncOpenMobile()
})

// Enforzar: si subdirectorio tiene item activo, no permitir cerrarlo
watch(openCatalogos, (v) => { if (!v && isCatalogosActive.value) openCatalogos.value = true })
watch(openUsuarios,  (v) => { if (!v && isUsuariosActive.value)  openUsuarios.value = true })
watch(openCatalogosMobile, (v) => { if (!v && isCatalogosActive.value) openCatalogosMobile.value = true })
watch(openUsuariosMobile,  (v) => { if (!v && isUsuariosActive.value)  openUsuariosMobile.value = true })

// Toggles
const toggleCatalogos = () => {
    const next = !openCatalogos.value
    openCatalogos.value = next
    if (next && !isUsuariosActive.value) openUsuarios.value = false
}
const toggleUsuarios = () => {
    const next = !openUsuarios.value
    openUsuarios.value = next
    if (next && !isCatalogosActive.value) openCatalogos.value = false
}
const toggleCatalogosMobile = () => {
    const next = !openCatalogosMobile.value
    openCatalogosMobile.value = next
    if (next && !isUsuariosActive.value) openUsuariosMobile.value = false
}
const toggleUsuariosMobile = () => {
    const next = !openUsuariosMobile.value
    openUsuariosMobile.value = next
    if (next && !isCatalogosActive.value) openCatalogosMobile.value = false
}

// Navegación
const goCatalogos = (routeName) => { openCatalogos.value = true; openUsuarios.value = false; emit('navigate', routeName) }
const goUsuarios  = (routeName) => { openUsuarios.value = true; openCatalogos.value = false; emit('navigate', routeName) }
const goCatalogosMobile = (routeName) => { openCatalogosMobile.value = true; openUsuariosMobile.value = false; emit('navigate', routeName); emit('close-sidebar') }
const goUsuariosMobile  = (routeName) => { openUsuariosMobile.value = true; openCatalogosMobile.value = false; emit('navigate', routeName); emit('close-sidebar') }

// UI helpers (los tuyos igual)
const groupBtnClass = (isActive) => ([
    'relative w-full flex items-center justify-between rounded-xl px-3 py-2 border transition',
    isActive ? 'border-emerald-400/30 bg-emerald-500/10 ring-1 ring-emerald-400/20'
        : 'border-white/10 bg-black/20 hover:bg-black/30'
])
const groupLabelClass = (isActive) => (isActive ? 'text-emerald-200' : 'text-white')
const caretClass = (open) => (['h-4 w-4 text-white/70 transition-transform duration-200', open ? 'rotate-0' : '-rotate-90'])
const itemBadgeClass = () =>
    'pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] px-2 py-0.5 rounded-full border ' +
    'bg-emerald-500/10 border-emerald-400/30 text-emerald-200 flex items-center gap-1'
const dotClass = () => 'inline-block h-1.5 w-1.5 rounded-full bg-emerald-300'
const collapsedDotClass = () =>
    'pointer-events-none absolute right-3 top-3 h-2 w-2 rounded-full bg-emerald-300 ring-2 ring-black/40'
const groupCollapsedDotClass = () =>
    'pointer-events-none absolute right-2 top-2 h-2 w-2 rounded-full bg-emerald-300 ring-2 ring-black/40'
const groupBadgeClass = (active) =>
    'text-[10px] px-2 py-0.5 rounded-full border flex items-center gap-1 ' +
    (active ? 'bg-emerald-500/10 border-emerald-400/30 text-emerald-200'
        : 'bg-white/5 border-white/10 text-white/70')

// Bloquear scroll en móvil
const lockBody = (lock) => {
    if (typeof document === 'undefined') return
    document.body.classList.toggle('overflow-hidden', !!lock)
}
watch(() => props.sidebarOpen, (v) => lockBody(v), { immediate: true })

onBeforeUnmount(() => lockBody(false))

</script>

<template>
    <!-- ✅ Wrapper raíz: ahora el móvil NO queda dentro del aside hidden -->
    <div>
        <!-- =========================
             SIDEBAR DESKTOP (md+)
        ========================== -->
        <aside
            :class="[
        'transition-all duration-300 bg-gradient-to-b from-alumun-guinda via-alumun-pino to-black shadow-2xl shadow-black/60',
        isCollapsed ? 'w-20' : 'w-64',
        ' md:flex flex-col'
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
                        <span class="block text-[10px] text-white/70">{{ labelPeriodoVigente }}</span>
                    </div>
                </div>

                <button
                    class="cursor-pointer text-white/70 hover:text-white text-xs bg-black/20 hover:bg-black/30 rounded-full p-1.5 ml-2 transition-transform duration-300"
                    :class="{ 'rotate-180': isCollapsed }"
                    @click="emit('toggle-collapse')"
                    :title="isCollapsed ? 'Expandir menú' : 'Colapsar menú'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 overflow-y-auto py-4 space-y-2">
                <!-- Inicio -->
                <div class="relative px-2">
                    <SidebarItem
                        v-if="can(' dashboard | all ')"
                        icon="🏠"
                        label="Inicio"
                        :active="isInicioActive"
                        :collapsed="isCollapsed"
                        @click="emit('navigate', 'dashboard')"
                    />
                    <span v-if="isInicioActive" :class="isCollapsed ? collapsedDotClass() : itemBadgeClass()">
            <template v-if="!isCollapsed">
              <span :class="dotClass()" /> ACTIVO
            </template>
          </span>
                </div>

                <!-- CATÁLOGOS -->
                <div v-if="catalogosCount > 0" class="px-2">
                    <button type="button" :class="groupBtnClass(isCatalogosActive)" @click="toggleCatalogos">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📚</span>
                            <span v-if="!isCollapsed" class="text-sm font-semibold" :class="groupLabelClass(isCatalogosActive)">
                Catálogos
              </span>
                        </div>

                        <div v-if="!isCollapsed" class="flex items-center gap-2">
              <span :class="groupBadgeClass(isCatalogosActive)">
                <span v-if="isCatalogosActive" :class="dotClass()" />
                {{ catalogosCount }}
              </span>

                            <svg xmlns="http://www.w3.org/2000/svg" :class="caretClass(openCatalogos)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <span v-if="isCollapsed && isCatalogosActive" :class="groupCollapsedDotClass()" />
                    </button>

                    <transition name="fade">
                        <div v-if="openCatalogos" class="mt-2 space-y-2 pl-2">
                            <div v-for="it in catalogosItems" :key="it.key" class="relative">
                                <SidebarItem
                                    :icon="it.icon"
                                    :label="it.label"
                                    :active="it.active"
                                    :collapsed="isCollapsed"
                                    @click="goCatalogos(it.go)"
                                />
                                <span v-if="it.active" :class="isCollapsed ? collapsedDotClass() : itemBadgeClass()">
                  <template v-if="!isCollapsed">
                    <span :class="dotClass()" /> ACTIVO
                  </template>
                </span>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- CFE -->
                <div class="relative px-2">
                    <SidebarItem
                        v-if="can(' procesar recibos | all ')"
                        icon="🛃"
                        label="Recibos"
                        :active="isRecibosActive"
                        :collapsed="isCollapsed"
                        @click="emit('navigate', 'cfe.importar.index')"
                    />
                    <span v-if="isRecibosActive" :class="isCollapsed ? collapsedDotClass() : itemBadgeClass()">
            <template v-if="!isCollapsed">
              <span :class="dotClass()" /> ACTIVO
            </template>
          </span>
                </div>

                <div class="relative px-2">
                    <SidebarItem
                        v-if="can(' subir archivos planos | all ')"
                        icon="🏢"
                        label="Archivos Planos"
                        :active="isUploadArchivosPlanos"
                        :collapsed="isCollapsed"
                        @click="emit('navigate', 'cfe.archivos-planos.index')"
                    />
                    <span v-if="isUploadArchivosPlanos" :class="isCollapsed ? collapsedDotClass() : itemBadgeClass()">
            <template v-if="!isCollapsed">
              <span :class="dotClass()" /> ACTIVO
            </template>
          </span>
                </div>

                <div class="relative px-2">
                    <SidebarItem
                        v-if="can(' conciliar recibos | all ')"
                        icon="🧩"
                        label="Conciliaciones"
                        :active="isConciliacionesActive"
                        :collapsed="isCollapsed"
                        @click="emit('navigate', 'cfe.conciliaciones.index')"
                    />
                    <span v-if="isConciliacionesActive" :class="isCollapsed ? collapsedDotClass() : itemBadgeClass()">
                        <template v-if="!isCollapsed">
                            <span :class="dotClass()" /> ACTIVO
                        </template>
                    </span>
                </div>


                <!-- Reportes / Config -->
                <div class="px-2">
                    <SidebarItem v-if="can(' consulta recibos | all ')" icon="📋" label="Reportes" :active="false" :collapsed="isCollapsed" />
                </div>
                <div class="px-2">
                    <SidebarItem v-if="can(' ver configuraciones | all ')" icon="⚙️" label="Configuración" :active="false" :collapsed="isCollapsed" />
                </div>

                <!-- USUARIOS -->
                <div v-if="usuariosCount > 0" class="px-2">
                    <button type="button" :class="groupBtnClass(isUsuariosActive)" @click="toggleUsuarios">
                        <div class="flex items-center gap-2">
                            <span class="text-base">👥</span>
                            <span v-if="!isCollapsed" class="text-sm font-semibold" :class="groupLabelClass(isUsuariosActive)">
                Usuarios
              </span>
                        </div>

                        <div v-if="!isCollapsed" class="flex items-center gap-2">
              <span :class="groupBadgeClass(isUsuariosActive)">
                <span v-if="isUsuariosActive" :class="dotClass()" />
                {{ usuariosCount }}
              </span>

                            <svg xmlns="http://www.w3.org/2000/svg" :class="caretClass(openUsuarios)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <span v-if="isCollapsed && isUsuariosActive" :class="groupCollapsedDotClass()" />
                    </button>

                    <transition name="fade">
                        <div v-if="openUsuarios" class="mt-2 space-y-2 pl-2">
                            <div v-for="it in usuariosItems" :key="it.key" class="relative">
                                <SidebarItem
                                    :icon="it.icon"
                                    :label="it.label"
                                    :active="it.active"
                                    :collapsed="isCollapsed"
                                    @click="goUsuarios(it.go)"
                                />
                                <span v-if="it.active" :class="isCollapsed ? collapsedDotClass() : itemBadgeClass()">
                  <template v-if="!isCollapsed">
                    <span :class="dotClass()" /> ACTIVO
                  </template>
                </span>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Logout -->
            </nav>
            <div class="border-t border-white/10 px-3 py-3">
                <button
                    class="cursor-pointer w-full flex items-center justify-center gap-2 text-xs bg-black/30 hover:bg-black/40 rounded-xl px-3 py-2"
                    @click="emit('logout')"
                >
                    <span>Salir</span>
                </button>
            </div>
        </aside>

        <!-- =========================
             SIDEBAR MÓVIL (sm)
             ✅ YA NO ESTÁ DENTRO DEL ASIDE hidden
        ========================== -->
        <transition name="overlay">
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-50 bg-black/60 md:hidden"
                @click="emit('close-sidebar')"
            >
                <transition name="drawer">
                    <aside
                        class="absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-gradient-to-b from-alumun-guinda via-alumun-pino to-black shadow-2xl flex flex-col"
                        @click.stop
                    >
                        <div class="flex items-center justify-between px-4 py-4 border-b border-white/10">
                            <div class="flex items-center gap-2">
                                <div class="h-9 w-9 rounded-2xl bg-black/30 flex items-center justify-center">
                                    <span class="font-bold text-sm">AP</span>
                                </div>
                                <div class="leading-tight">
                                    <span class="font-semibold tracking-wide">AluMun</span>
                                    <span class="block text-[10px] text-white/70">{{ labelPeriodoVigente }}</span>
                                </div>
                            </div>

                            <button
                                class="cursor-pointer text-white/70 hover:text-white text-xs bg-black/20 hover:bg-black/30 rounded-full p-1.5"
                                @click="emit('close-sidebar')"
                            >
                                ✕
                            </button>
                        </div>

                        <nav class="flex-1 overflow-y-auto py-4 space-y-2">
                            <SidebarItem
                                v-if="can(' dashboard | all ')"
                                icon="🏠"
                                label="Inicio"
                                :active="isInicioActive"
                                @click="emit('navigate', 'dashboard'); emit('close-sidebar')"
                            />

                            <!-- Catálogos móvil -->
                            <div v-if="catalogosCount > 0" class="px-2">
                                <button type="button" :class="groupBtnClass(isCatalogosActive)" @click="toggleCatalogosMobile">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">📚</span>
                                        <span class="text-sm font-semibold" :class="groupLabelClass(isCatalogosActive)">Catálogos</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                    <span :class="groupBadgeClass(isCatalogosActive)">
                      <span v-if="isCatalogosActive" :class="dotClass()" />
                      {{ catalogosCount }}
                    </span>

                                        <svg xmlns="http://www.w3.org/2000/svg" :class="caretClass(openCatalogosMobile)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </button>

                                <transition name="fade">
                                    <div v-if="openCatalogosMobile" class="mt-2 space-y-2 pl-2">
                                        <SidebarItem
                                            v-for="it in catalogosItems"
                                            :key="it.key"
                                            :icon="it.icon"
                                            :label="it.label"
                                            :active="it.active"
                                            @click="goCatalogosMobile(it.go)"
                                        />
                                    </div>
                                </transition>
                            </div>

                            <SidebarItem
                                v-if="can(' procesar recibos | all ')"
                                icon="🛃"
                                label="Recibos"
                                :active="isRecibosActive"
                                @click="emit('navigate', 'cfe.importar.index'); emit('close-sidebar')"
                            />

                            <SidebarItem
                                v-if="can(' subir archivos planos | all ')"
                                icon="🧾"
                                label="Archivos Planos"
                                :active="isUploadArchivosPlanos"
                                @click="emit('navigate', 'cfe.archivos-planos.index'); emit('close-sidebar')"
                            />
                            <SidebarItem
                                v-if="can(' conciliar recibos | all ')"
                                icon="🧩"
                                label="Conciliaciones"
                                :active="isConciliacionesActive"
                                @click="emit('navigate', 'cfe.conciliaciones.index'); emit('close-sidebar')"
                            />

                            <SidebarItem v-if="can(' consulta recibos | all ')" icon="📋" label="Reportes" :active="false" @click="emit('close-sidebar')" />
                            <SidebarItem v-if="can(' ver configuraciones | all ')" icon="⚙️" label="Configuración" :active="false" @click="emit('close-sidebar')" />

                            <!-- Usuarios móvil -->
                            <div v-if="usuariosCount > 0" class="px-2">
                                <button type="button" :class="groupBtnClass(isUsuariosActive)" @click="toggleUsuariosMobile">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">👥</span>
                                        <span class="text-sm font-semibold" :class="groupLabelClass(isUsuariosActive)">Usuarios</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                    <span :class="groupBadgeClass(isUsuariosActive)">
                      <span v-if="isUsuariosActive" :class="dotClass()" />
                      {{ usuariosCount }}
                    </span>

                                        <svg xmlns="http://www.w3.org/2000/svg" :class="caretClass(openUsuariosMobile)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </button>

                                <transition name="fade">
                                    <div v-if="openUsuariosMobile" class="mt-2 space-y-2 pl-2">
                                        <SidebarItem
                                            v-for="it in usuariosItems"
                                            :key="it.key"
                                            :icon="it.icon"
                                            :label="it.label"
                                            :active="it.active"
                                            @click="goUsuariosMobile(it.go)"
                                        />
                                    </div>
                                </transition>
                            </div>
                        </nav>

                        <div class="border-t border-white/10 px-3 py-3">
                            <button
                                class="cursor-pointer w-full flex items-center justify-center gap-2 text-sm bg-black/30 hover:bg-black/40 rounded-xl px-3 py-2"
                                @click="emit('logout')"
                            >
                                <span>Salir</span>
                            </button>
                        </div>
                    </aside>
                </transition>
            </div>
        </transition>

        <!-- ✅ Widget flotante: Periodo Vigente (siempre visible desktop + móvil) -->
        <div
            class="fixed z-[80]
             left-[calc(env(safe-area-inset-left)+0.75rem)]
             bottom-[calc(env(safe-area-inset-bottom)+0.75rem)]"
        >
            <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-black/50 backdrop-blur px-3 py-2 shadow-2xl shadow-black/40">
                <div class="h-9 w-9 rounded-xl bg-black/30 flex items-center justify-center">
                    <span class="text-sm">📅</span>
                </div>

                <div class="leading-tight">
                    <div class="text-xs font-semibold text-white truncate max-w-[14rem]">
                        {{ labelPeriodoVigente }}
                    </div>
                    <div class="text-[10px] text-white/70">
                        Periodo Vigente
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

/* Overlay fade */
.overlay-enter-active,
.overlay-leave-active { transition: opacity .18s ease; }
.overlay-enter-from,
.overlay-leave-to { opacity: 0; }

/* Drawer slide-in */
.drawer-enter-active,
.drawer-leave-active { transition: transform .22s ease; }
.drawer-enter-from,
.drawer-leave-to { transform: translateX(-100%); }
</style>
