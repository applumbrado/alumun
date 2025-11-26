import { ref, computed } from 'vue'

export function useNumberInput(options = {}) {
    const {
        maxInteger = 12,
        maxDecimal = 4,
        initialValue = '',
        allowNegative = false,
        decimalSeparator = '.'
    } = options

    const internalValue = ref(initialValue)

    const formattedValue = computed({
        get: () => internalValue.value,
        set: (newValue) => {
            if (newValue === '') {
                internalValue.value = ''
                return
            }

            const allowedChars = allowNegative ? `0-9${decimalSeparator}-` : `0-9${decimalSeparator}`
            let cleaned = newValue.replace(new RegExp(`[^${allowedChars}]`, 'g'), '')

            if (allowNegative && cleaned.includes('-')) {
                if (cleaned.indexOf('-') > 0) {
                    cleaned = cleaned.replace(/-/g, '')
                } else {
                    cleaned = '-' + cleaned.replace(/-/g, '')
                }
            }

            const parts = cleaned.split(decimalSeparator)

            if (parts.length > 2) {
                cleaned = parts[0] + decimalSeparator + parts.slice(1).join('')
            }

            if (parts[0] && parts[0].replace('-', '').length > maxInteger) {
                const integerPart = parts[0].replace('-', '').substring(0, maxInteger)
                cleaned = (parts[0].startsWith('-') ? '-' : '') + integerPart +
                    (parts[1] ? decimalSeparator + parts[1] : '')
            }

            if (parts[1] && parts[1].length > maxDecimal) {
                cleaned = parts[0] + decimalSeparator + parts[1].substring(0, maxDecimal)
            }

            const validationRegex = allowNegative
                ? new RegExp(`^-?\\d{0,${maxInteger}}(\\${decimalSeparator}\\d{0,${maxDecimal}})?$`)
                : new RegExp(`^\\d{0,${maxInteger}}(\\${decimalSeparator}\\d{0,${maxDecimal}})?$`)

            if (validationRegex.test(cleaned) || cleaned === '') {
                internalValue.value = cleaned
            }
        }
    })

    const isValid = computed(() => {
        if (internalValue.value === '') return true

        const finalRegex = allowNegative
            ? new RegExp(`^-?\\d{1,${maxInteger}}(\\${decimalSeparator}\\d{1,${maxDecimal}})?$`)
            : new RegExp(`^\\d{1,${maxInteger}}(\\${decimalSeparator}\\d{1,${maxDecimal}})?$`)

        return finalRegex.test(internalValue.value)
    })

    const numericValue = computed(() => {
        if (!internalValue.value) return null
        return parseFloat(internalValue.value.replace(decimalSeparator, '.'))
    })

    const reset = () => {
        internalValue.value = initialValue
    }

    const setValue = (value) => {
        internalValue.value = String(value)
    }

    return {
        value: formattedValue,
        isValid,
        numericValue,
        reset,
        setValue
    }
}
