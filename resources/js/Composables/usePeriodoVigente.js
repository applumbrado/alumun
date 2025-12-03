// resources/js/composables/usePeriodoVigente.js
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePeriodoVigente() {
    const page = usePage()

    const periodoVigente = computed(() => page.props.periodo_vigente || null)

    const labelPeriodoVigente = computed(() =>
        periodoVigente.value ? periodoVigente.value.label : 'Sin periodo vigente'
    )

    return {
        periodoVigente,
        labelPeriodoVigente,
    }
}
