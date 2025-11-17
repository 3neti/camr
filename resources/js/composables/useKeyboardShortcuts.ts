import { onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

export interface KeyboardShortcut {
  key: string
  ctrl?: boolean
  meta?: boolean
  shift?: boolean
  alt?: boolean
  action: () => void
  description: string
  category?: string
}

export function useKeyboardShortcuts() {
  const shortcuts = new Map<string, KeyboardShortcut>()

  function registerShortcut(shortcut: KeyboardShortcut) {
    const id = getShortcutId(shortcut)
    shortcuts.set(id, shortcut)
  }

  function unregisterShortcut(shortcut: KeyboardShortcut) {
    const id = getShortcutId(shortcut)
    shortcuts.delete(id)
  }

  function getShortcutId(shortcut: Pick<KeyboardShortcut, 'key' | 'ctrl' | 'meta' | 'shift' | 'alt'>): string {
    const modifiers = []
    if (shortcut.ctrl) modifiers.push('ctrl')
    if (shortcut.meta) modifiers.push('meta')
    if (shortcut.shift) modifiers.push('shift')
    if (shortcut.alt) modifiers.push('alt')
    return [...modifiers, shortcut.key.toLowerCase()].join('+')
  }

  function handleKeyDown(event: KeyboardEvent) {
    // Don't trigger shortcuts when typing in inputs, textareas, or contenteditable
    const target = event.target as HTMLElement
    if (
      target.tagName === 'INPUT' ||
      target.tagName === 'TEXTAREA' ||
      target.isContentEditable
    ) {
      // Exception: allow Escape key even in inputs
      if (event.key !== 'Escape') {
        return
      }
    }

    const id = getShortcutId({
      key: event.key,
      ctrl: event.ctrlKey,
      meta: event.metaKey,
      shift: event.shiftKey,
      alt: event.altKey,
    })

    const shortcut = shortcuts.get(id)
    if (shortcut) {
      event.preventDefault()
      shortcut.action()
    }
  }

  onMounted(() => {
    window.addEventListener('keydown', handleKeyDown)
  })

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown)
    shortcuts.clear()
  })

  return {
    registerShortcut,
    unregisterShortcut,
    shortcuts,
  }
}

// Predefined common shortcuts
export const commonShortcuts = {
  // Navigation
  goToDashboard: (): KeyboardShortcut => ({
    key: 'd',
    meta: true,
    shift: true,
    action: () => router.visit('/dashboard'),
    description: 'Go to Dashboard',
    category: 'Navigation',
  }),
  
  goToSites: (): KeyboardShortcut => ({
    key: 's',
    meta: true,
    shift: true,
    action: () => router.visit('/sites'),
    description: 'Go to Sites',
    category: 'Navigation',
  }),
  
  goToGateways: (): KeyboardShortcut => ({
    key: 'g',
    meta: true,
    shift: true,
    action: () => router.visit('/gateways'),
    description: 'Go to Gateways',
    category: 'Navigation',
  }),
  
  goToMeters: (): KeyboardShortcut => ({
    key: 'm',
    meta: true,
    shift: true,
    action: () => router.visit('/meters'),
    description: 'Go to Meters',
    category: 'Navigation',
  }),
  
  goToReports: (): KeyboardShortcut => ({
    key: 'r',
    meta: true,
    shift: true,
    action: () => router.visit('/reports'),
    description: 'Go to Reports',
    category: 'Navigation',
  }),
  
  // Actions
  refresh: (): KeyboardShortcut => ({
    key: 'r',
    meta: true,
    action: () => router.reload(),
    description: 'Refresh page',
    category: 'Actions',
  }),
  
  goBack: (): KeyboardShortcut => ({
    key: '[',
    meta: true,
    action: () => window.history.back(),
    description: 'Go back',
    category: 'Actions',
  }),
  
  goForward: (): KeyboardShortcut => ({
    key: ']',
    meta: true,
    action: () => window.history.forward(),
    description: 'Go forward',
    category: 'Actions',
  }),
}
