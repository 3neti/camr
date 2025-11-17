# Client-Side Validation System Guide

This guide explains how to use the CAMR application's client-side validation system.

## Overview

The validation system provides real-time form validation with helpful error messages and hints. It consists of:

1. **`useValidation` composable** - Core validation logic
2. **`validationRules` library** - Pre-built validation rules
3. **`FormField` component** - Consistent field layout with error display

## Quick Start

### 1. Import Required Dependencies

```typescript
import { useValidation, validationRules } from '@/composables/useValidation'
import FormField from '@/components/FormField.vue'
import { onMounted, watch } from 'vue'
```

### 2. Setup Validation in Your Form

```typescript
const form = useForm({
  email: '',
  password: '',
  age: '',
})

const validation = useValidation()

onMounted(() => {
  // Register fields with validation rules
  validation.registerField('email', [
    validationRules.required('Email is required'),
    validationRules.email(),
  ])
  
  validation.registerField('password', [
    validationRules.required(),
    validationRules.minLength(8, 'Password must be at least 8 characters'),
  ])
  
  validation.registerField('age', [
    validationRules.numeric('Age must be a number'),
    validationRules.min(18, 'Must be at least 18 years old'),
  ])
})

// Watch fields and validate on change
watch(() => form.email, (value) => {
  validation.markDirty('email')
  validation.validateField('email', value)
})

watch(() => form.password, (value) => {
  validation.markDirty('password')
  validation.validateField('password', value)
})

// Validate all before submission
const submit = () => {
  const isValid = validation.validateAll({
    email: form.email,
    password: form.password,
    age: form.age,
  })

  if (!isValid) {
    Object.keys(validation.fields.value).forEach(field => {
      validation.touchField(field)
    })
    return
  }

  form.post('/submit')
}
```

### 3. Use FormField in Template

```vue
<template>
  <form @submit.prevent="submit">
    <FormField 
      label="Email Address" 
      name="email" 
      :error="validation.shouldShowError('email') ? validation.getError('email') : undefined"
      hint="We'll never share your email"
      required
    >
      <Input
        v-model="form.email"
        type="email"
        @blur="() => validation.touchField('email')"
      />
    </FormField>

    <FormField 
      label="Password" 
      name="password" 
      :error="validation.shouldShowError('password') ? validation.getError('password') : undefined"
      required
    >
      <Input
        v-model="form.password"
        type="password"
        @blur="() => validation.touchField('password')"
      />
    </FormField>

    <Button type="submit">Submit</Button>
  </form>
</template>
```

## Available Validation Rules

### `required(message?)`
Ensures field has a value.
```typescript
validationRules.required('This field is required')
```

### `minLength(min, message?)`
Validates minimum string length.
```typescript
validationRules.minLength(3, 'Must be at least 3 characters')
```

### `maxLength(max, message?)`
Validates maximum string length.
```typescript
validationRules.maxLength(100, 'Must be no more than 100 characters')
```

### `email(message?)`
Validates email format.
```typescript
validationRules.email('Must be a valid email address')
```

### `numeric(message?)`
Ensures value is a number.
```typescript
validationRules.numeric('Must be a number')
```

### `min(min, message?)`
Validates minimum numeric value.
```typescript
validationRules.min(0, 'Must be at least 0')
```

### `max(max, message?)`
Validates maximum numeric value.
```typescript
validationRules.max(100, 'Must be no more than 100')
```

### `pattern(regex, message)`
Validates against custom regex.
```typescript
validationRules.pattern(/^[A-Z]{3}$/, 'Must be 3 uppercase letters')
```

### `url(message?)`
Validates URL format.
```typescript
validationRules.url('Must be a valid URL')
```

### `ipAddress(message?)`
Validates IPv4 address format.
```typescript
validationRules.ipAddress('Must be a valid IP address')
```

### `macAddress(message?)`
Validates MAC address format (XX:XX:XX:XX:XX:XX).
```typescript
validationRules.macAddress('Must be a valid MAC address')
```

### `custom(validator, message)`
Create custom validation logic.
```typescript
validationRules.custom(
  (value) => value !== 'admin',
  'Username cannot be "admin"'
)
```

## FormField Component Props

| Prop | Type | Description |
|------|------|-------------|
| `label` | `string` | Field label text (required) |
| `name` | `string` | Field identifier (required) |
| `error` | `string?` | Error message to display |
| `hint` | `string?` | Helpful hint text |
| `required` | `boolean?` | Shows red asterisk (*) |
| `showSuccess` | `boolean?` | Shows green checkmark when valid |

## Validation State

### Dirty vs Touched

- **Dirty**: Field value has changed from initial value
- **Touched**: Field has been focused and blurred (lost focus)

Use `shouldShowError()` to show errors only after user interaction:

```typescript
validation.shouldShowError('fieldName') // Shows error if touched OR dirty
```

### Methods

| Method | Description |
|--------|-------------|
| `registerField(name, rules)` | Register field with validation rules |
| `validateField(name, value)` | Validate single field |
| `validateAll(formData)` | Validate all registered fields |
| `touchField(name)` | Mark field as touched |
| `markDirty(name)` | Mark field as dirty |
| `hasError(name)` | Check if field has error |
| `getError(name)` | Get error message |
| `shouldShowError(name)` | Check if error should be displayed |
| `clearErrors()` | Clear all errors |
| `clearFieldError(name)` | Clear single field error |

## Best Practices

### 1. Validate on Blur for Required Fields

Mark required fields as touched when user leaves the field:

```vue
<Input
  v-model="form.email"
  @blur="() => validation.touchField('email')"
/>
```

### 2. Validate on Change for Format Validation

Watch field values and validate immediately for format rules:

```typescript
watch(() => form.ip_address, (value) => {
  if (value) {
    validation.markDirty('ip_address')
    validation.validateField('ip_address', value)
  }
})
```

### 3. Combine Backend and Client Errors

Show both client-side and server-side errors:

```vue
<FormField 
  :error="validation.shouldShowError('email') 
    ? validation.getError('email') 
    : (form.errors.email || undefined)"
/>
```

### 4. Prevent Invalid Submissions

Always validate all fields before submitting:

```typescript
const submit = () => {
  const isValid = validation.validateAll(form.data())

  if (!isValid) {
    // Mark all as touched to show all errors
    Object.keys(validation.fields.value).forEach(field => {
      validation.touchField(field)
    })
    return
  }

  form.post('/submit')
}
```

### 5. Use Hints for User Guidance

Provide helpful hints for expected formats:

```vue
<FormField 
  label="MAC Address" 
  hint="Format: 00:11:22:33:44:55"
>
  <Input v-model="form.mac_address" />
</FormField>
```

## Example: Complete Form with Validation

See `resources/js/pages/gateways/Create.vue` for a full implementation example with:
- Multiple field types (text, select, checkbox)
- Real-time validation
- Pre-submission validation
- Helpful hints and error messages
- Integration with Inertia form handling

## Migration Guide

To add validation to existing forms:

1. Import validation composable and FormField
2. Setup validation in `onMounted`
3. Add watchers for real-time validation
4. Replace existing field markup with FormField components
5. Add validation check in submit handler

## Common Patterns

### Conditional Validation

```typescript
// Only validate if field has a value (optional field)
watch(() => form.optional_email, (value) => {
  if (value) {
    validation.validateField('optional_email', value)
  } else {
    validation.clearFieldError('optional_email')
  }
})
```

### Multiple Rules Per Field

```typescript
validation.registerField('password', [
  validationRules.required('Password is required'),
  validationRules.minLength(8, 'At least 8 characters'),
  validationRules.pattern(
    /[A-Z]/, 
    'Must contain uppercase letter'
  ),
  validationRules.pattern(
    /[0-9]/, 
    'Must contain number'
  ),
])
```

### Dependent Field Validation

```typescript
// Validate confirm password matches password
validation.registerField('password_confirmation', [
  validationRules.custom(
    (value) => value === form.password,
    'Passwords must match'
  ),
])
```

## Support

For questions or issues with the validation system, refer to the implementation in `resources/js/pages/gateways/Create.vue` or check the composable at `resources/js/composables/useValidation.ts`.
