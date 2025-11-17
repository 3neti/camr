import { ref, computed, watch } from 'vue'

export interface ValidationRule {
  validate: (value: any) => boolean
  message: string
}

export interface FieldValidation {
  rules: ValidationRule[]
  dirty: boolean
  touched: boolean
}

export function useValidation() {
  const fields = ref<Record<string, FieldValidation>>({})
  const errors = ref<Record<string, string>>({})

  /**
   * Register a field with validation rules
   */
  function registerField(fieldName: string, rules: ValidationRule[]) {
    fields.value[fieldName] = {
      rules,
      dirty: false,
      touched: false,
    }
  }

  /**
   * Mark field as touched (user has interacted with it)
   */
  function touchField(fieldName: string) {
    if (fields.value[fieldName]) {
      fields.value[fieldName].touched = true
    }
  }

  /**
   * Mark field as dirty (value has changed)
   */
  function markDirty(fieldName: string) {
    if (fields.value[fieldName]) {
      fields.value[fieldName].dirty = true
    }
  }

  /**
   * Validate a single field
   */
  function validateField(fieldName: string, value: any): boolean {
    const field = fields.value[fieldName]
    if (!field) return true

    // Find first failing rule
    const failedRule = field.rules.find(rule => !rule.validate(value))
    
    if (failedRule) {
      errors.value[fieldName] = failedRule.message
      return false
    } else {
      delete errors.value[fieldName]
      return true
    }
  }

  /**
   * Validate all registered fields
   */
  function validateAll(formData: Record<string, any>): boolean {
    let isValid = true
    
    Object.keys(fields.value).forEach(fieldName => {
      const fieldIsValid = validateField(fieldName, formData[fieldName])
      if (!fieldIsValid) {
        isValid = false
      }
    })

    return isValid
  }

  /**
   * Clear all errors
   */
  function clearErrors() {
    errors.value = {}
  }

  /**
   * Clear error for specific field
   */
  function clearFieldError(fieldName: string) {
    delete errors.value[fieldName]
  }

  /**
   * Check if field has error
   */
  function hasError(fieldName: string): boolean {
    return !!errors.value[fieldName]
  }

  /**
   * Get error message for field
   */
  function getError(fieldName: string): string | undefined {
    return errors.value[fieldName]
  }

  /**
   * Check if field should show error (touched or dirty)
   */
  function shouldShowError(fieldName: string): boolean {
    const field = fields.value[fieldName]
    return !!field && (field.touched || field.dirty) && hasError(fieldName)
  }

  return {
    fields,
    errors,
    registerField,
    touchField,
    markDirty,
    validateField,
    validateAll,
    clearErrors,
    clearFieldError,
    hasError,
    getError,
    shouldShowError,
  }
}

// Common validation rules
export const validationRules = {
  required: (message = 'This field is required'): ValidationRule => ({
    validate: (value: any) => {
      if (typeof value === 'string') return value.trim().length > 0
      if (Array.isArray(value)) return value.length > 0
      return value !== null && value !== undefined && value !== ''
    },
    message,
  }),

  minLength: (min: number, message?: string): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true // Let required rule handle empty values
      return String(value).length >= min
    },
    message: message || `Must be at least ${min} characters`,
  }),

  maxLength: (max: number, message?: string): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true
      return String(value).length <= max
    },
    message: message || `Must be no more than ${max} characters`,
  }),

  email: (message = 'Must be a valid email address'): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return emailRegex.test(String(value))
    },
    message,
  }),

  numeric: (message = 'Must be a number'): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true
      return !isNaN(Number(value))
    },
    message,
  }),

  min: (min: number, message?: string): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true
      return Number(value) >= min
    },
    message: message || `Must be at least ${min}`,
  }),

  max: (max: number, message?: string): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true
      return Number(value) <= max
    },
    message: message || `Must be no more than ${max}`,
  }),

  pattern: (regex: RegExp, message: string): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true
      return regex.test(String(value))
    },
    message,
  }),

  url: (message = 'Must be a valid URL'): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true
      try {
        new URL(String(value))
        return true
      } catch {
        return false
      }
    },
    message,
  }),

  ipAddress: (message = 'Must be a valid IP address'): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true
      const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/
      if (!ipRegex.test(String(value))) return false
      const parts = String(value).split('.')
      return parts.every(part => Number(part) >= 0 && Number(part) <= 255)
    },
    message,
  }),

  macAddress: (message = 'Must be a valid MAC address'): ValidationRule => ({
    validate: (value: any) => {
      if (!value) return true
      const macRegex = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/
      return macRegex.test(String(value))
    },
    message,
  }),

  custom: (validator: (value: any) => boolean, message: string): ValidationRule => ({
    validate: validator,
    message,
  }),
}
