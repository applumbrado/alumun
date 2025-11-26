import { computed } from 'vue'
import { useNumberInput } from './useNumberInput'

export function useFormNumberInputDirect(form, fieldName, options = {}) {

    // console.log(fieldName, fieldName, options)
    const numberInput = useNumberInput({
        ...options,
        initialValue: form.value[fieldName]
    })

    // Retornar directamente la referencia reactiva (sin el objeto wrapper)
    const directValue = computed({
        get: () => numberInput.value,
        set: (value) => {
            numberInput.value = value
            form.value[fieldName] = value
        }
    })

    // Asignar las propiedades adicionales directamente al computed
    directValue.isValid = numberInput.isValid
    directValue.numericValue = numberInput.numericValue
    directValue.reset = numberInput.reset

    return directValue
}
