import { computed } from 'vue'
import { useNumberInput } from './useNumberInput'

export function useFormNumberInput(form, fieldName, options = {}) {
    // Crear el input numérico con el valor inicial del form
    const numberInput = useNumberInput({
        ...options,
        initialValue: form.value[fieldName]
    })

    // Computed que sincroniza automáticamente con el form
    const syncedValue = computed({
        get: () => numberInput.value,
        set: (value) => {
            numberInput.value = value
            form.value[fieldName] = value
        }
    })

    return {
        value: syncedValue,
        isValid: numberInput.isValid,
        numericValue: numberInput.numericValue,
        reset: numberInput.reset
    }
}
