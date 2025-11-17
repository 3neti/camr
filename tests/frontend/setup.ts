import { vi } from 'vitest'
import { config } from '@vue/test-utils'

// Mock Inertia.js
vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn(),
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    reload: vi.fn(),
    replace: vi.fn(),
    remember: vi.fn(),
    restore: vi.fn(),
  },
  Link: {
    name: 'Link',
    template: '<a><slot /></a>',
  },
  Head: {
    name: 'Head',
    template: '<div><slot /></div>',
  },
  usePage: vi.fn(() => ({
    props: {
      value: {
        auth: {
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            role: 'admin',
          },
        },
        flash: {},
        errors: {},
      },
    },
    url: '/',
    component: 'TestComponent',
    version: '1',
    rememberedState: {},
    scrollRegions: [],
  })),
}))

// Mock route helper (from Laravel Wayfinder)
vi.mock('@/routes', () => ({
  dashboard: vi.fn(() => ({ url: '/dashboard' })),
  sites: {
    index: vi.fn(() => ({ url: '/sites' })),
    create: vi.fn(() => ({ url: '/sites/create' })),
    show: vi.fn((id: number) => ({ url: `/sites/${id}` })),
    edit: vi.fn((id: number) => ({ url: `/sites/${id}/edit` })),
  },
  gateways: {
    index: vi.fn(() => ({ url: '/gateways' })),
    show: vi.fn((id: number) => ({ url: `/gateways/${id}` })),
  },
  meters: {
    index: vi.fn(() => ({ url: '/meters' })),
    show: vi.fn((id: number) => ({ url: `/meters/${id}` })),
  },
  locations: {
    index: vi.fn(() => ({ url: '/locations' })),
    show: vi.fn((id: number) => ({ url: `/locations/${id}` })),
  },
  users: {
    index: vi.fn(() => ({ url: '/users' })),
  },
  profile: {
    edit: vi.fn(() => ({ url: '/profile' })),
  },
  login: vi.fn(() => ({ url: '/login' })),
  logout: vi.fn(() => ({ url: '/logout' })),
}))

// Configure Vue Test Utils
config.global.mocks = {
  $route: {
    params: {},
    query: {},
  },
  $router: {
    push: vi.fn(),
    replace: vi.fn(),
    go: vi.fn(),
    back: vi.fn(),
  },
}

// Mock window.matchMedia for responsive components
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {}

  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value.toString()
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      store = {}
    },
  }
})()

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
})

// Reset mocks before each test
beforeEach(() => {
  vi.clearAllMocks()
  localStorage.clear()
})
