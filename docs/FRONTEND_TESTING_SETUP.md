# Frontend Testing Setup with Vitest

This document describes the frontend testing infrastructure set up for the CAMR application.

## Setup Completed

### 1. Installed Dependencies

```bash
npm install -D vitest @vitest/ui @vue/test-utils happy-dom jsdom
```

Packages installed:
- **vitest**: Fast test runner powered by Vite
- **@vitest/ui**: Web-based UI for test results
- **@vue/test-utils**: Official testing utilities for Vue 3
- **happy-dom**: Lightweight DOM implementation for tests
- **jsdom**: Alternative DOM implementation

### 2. Configuration

**vitest.config.ts** created with:
- Vue plugin support
- JSdom environment for DOM testing
- Path aliases matching the main Vite config (`@`, `@/components`, etc.)
- Setup files for test initialization
- Coverage configuration with v8 provider
- Exclusions for generated UI components

### 3. Test Setup File

**tests/frontend/setup.ts** provides:
- Mocked Inertia.js router and components
- Mocked Laravel Wayfinder routes  
- Vue Test Utils global configuration
- window.matchMedia mock for responsive components
- localStorage mock
- Auto-reset of mocks before each test

### 4. NPM Scripts

Added to package.json:
```json
"test": "vitest",              // Run tests in watch mode
"test:ui": "vitest --ui",      // Run with web UI
"test:run": "vitest run",      // Run once and exit
"test:coverage": "vitest run --coverage"  // With coverage report
```

## Writing Tests

### Composable Tests

Tests for composables should be placed in `tests/frontend/composables/*.test.ts`.

Example test structure:

```typescript
import { describe, it, expect, beforeEach } from 'vitest'
import { useYourComposable } from '@/composables/useYourComposable'

describe('useYourComposable', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('should do something', () => {
    const { someValue, someMethod } = useYourComposable({
      option1: 'value1',
    })
    
    expect(someValue.value).toBe('expected')
  })
})
```

### Component Tests

Component tests should be placed in `tests/frontend/components/*.test.ts`.

Example:

```typescript
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import YourComponent from '@/components/YourComponent.vue'

describe('YourComponent', () => {
  it('renders properly', () => {
    const wrapper = mount(YourComponent, {
      props: {
        title: 'Test Title',
      },
    })

    expect(wrapper.text()).toContain('Test Title')
  })

  it('emits events', async () => {
    const wrapper = mount(YourComponent)
    
    await wrapper.find('button').trigger('click')
    
    expect(wrapper.emitted('click')).toBeTruthy()
  })
})
```

## Key Composables to Test

Based on Option C features implemented:

1. **useFilterPresets** - `resources/js/composables/useFilterPresets.ts`
   - API: `useFilterPresets({ storageKey, routeUrl })`
   - Test: save, load, apply, delete presets
   - Test: localStorage persistence
   - Test: preset matching

2. **useColumnPreferences** - `resources/js/composables/useColumnPreferences.ts`
   - API: `useColumnPreferences({ storageKey, defaultColumns })`
   - Test: show/hide columns
   - Test: locked columns
   - Test: localStorage persistence
   - Test: reset to defaults

3. **useKeyboardShortcuts** - `resources/js/composables/useKeyboardShortcuts.ts`
   - Test: shortcut registration
   - Test: keyboard event handling
   - Test: OS detection (Mac vs PC)

4. **useNotification** - `resources/js/composables/useNotification.ts`
   - Test: notification creation
   - Test: different types (success, error, warning, info)
   - Test: action buttons
   - Test: promise-based notifications

## Key Components to Test

1. **FilterPresets.vue** - `resources/js/components/FilterPresets.vue`
   - Save preset dialog
   - Apply preset dropdown
   - Delete preset confirmation

2. **ColumnPreferences.vue** - `resources/js/components/ColumnPreferences.vue`
   - Column visibility toggles
   - Show/Hide all buttons
   - Locked columns display

3. **GlobalSearch.vue** - `resources/js/components/GlobalSearch.vue`
   - Search input with debounce
   - Results display by category
   - Keyboard navigation
   - ⌘K / Ctrl+K shortcut

4. **KeyboardShortcutsHelp.vue** - `resources/js/components/KeyboardShortcutsHelp.vue`
   - Shortcut list display
   - Category grouping
   - Platform-specific keys

## Running Tests

```bash
# Watch mode (recommended for development)
npm run test

# Single run (for CI)
npm run test:run

# With UI
npm run test:ui

# With coverage
npm run test:coverage
```

## Testing Best Practices

1. **Isolate Tests**: Each test should be independent
2. **Clear State**: Use `beforeEach` to reset state
3. **Mock External Dependencies**: Use vi.mock() for external modules
4. **Test User Behavior**: Focus on what users see and do
5. **Use Descriptive Names**: Test names should describe the behavior
6. **Avoid Implementation Details**: Test outcomes, not internals
7. **Keep Tests Fast**: Mock heavy operations
8. **Test Edge Cases**: Empty states, errors, boundaries

## Coverage Goals

Aim for:
- **80%+ line coverage** for composables
- **70%+ line coverage** for components
- **90%+ coverage** for critical user paths

## Next Steps

1. Write tests for useFilterPresets composable
2. Write tests for useColumnPreferences composable
3. Write tests for FilterPresets component
4. Write tests for ColumnPreferences component
5. Write tests for GlobalSearch component
6. Add snapshot tests for UI components
7. Set up pre-commit hooks to run tests
8. Add test coverage to CI/CD pipeline

## Troubleshooting

### Tests failing with "Cannot find module"
- Check path aliases in vitest.config.ts
- Ensure setup.ts is loaded properly

### DOM-related errors
- Verify jsdom environment is set
- Check window mocks in setup.ts

### Router/Inertia errors
- Verify Inertia mocks in setup.ts
- Check router.visit calls are mocked

### Type errors
- Ensure @types/node is installed
- Check tsconfig.json includes test files

## Resources

- [Vitest Documentation](https://vitest.dev/)
- [Vue Test Utils](https://test-utils.vuejs.org/)
- [Testing Library](https://testing-library.com/)
- [Vitest UI](https://vitest.dev/guide/ui.html)
