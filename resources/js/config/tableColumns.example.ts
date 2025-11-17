/**
 * Centralized Table Column Configuration
 * 
 * This file defines the default column configurations for all index tables in the application.
 * Environment-specific: This file should NOT be committed to version control.
 * 
 * Column Properties:
 * - key: Unique identifier for the column (matches data property)
 * - label: Display name for the column header
 * - visible: Default visibility state
 * - locked: If true, column cannot be hidden or reordered by users
 * - sortable: If true, column supports sorting
 * - order: Display order (lower numbers appear first)
 * - width: CSS width value (e.g., '200px', '20%', 'auto')
 * - alignment: Text alignment ('left' | 'center' | 'right')
 * - formatter: Optional function name for formatting cell values
 */

export type ColumnAlignment = 'left' | 'center' | 'right'
export type ColumnFormatter = 'date' | 'datetime' | 'currency' | 'boolean' | 'badge' | 'custom'

export interface ColumnConfig {
  key: string
  label: string
  visible: boolean
  locked?: boolean
  sortable?: boolean
  order: number
  width?: string
  alignment?: ColumnAlignment
  formatter?: ColumnFormatter
}

export interface TableConfig {
  storageKey: string
  columns: ColumnConfig[]
}

/**
 * Table configurations for all index pages
 */
const tableConfigs: Record<string, TableConfig> = {
  /**
   * Sites Index Table
   */
  sites: {
    storageKey: 'sites-column-preferences',
    columns: [
      {
        key: 'checkbox',
        label: 'Select',
        visible: true,
        locked: true,
        sortable: false,
        order: 0,
        width: '48px',
        alignment: 'center',
      },
      {
        key: 'code',
        label: 'Code',
        visible: true,
        locked: true,
        sortable: true,
        order: 1,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'company',
        label: 'Company',
        visible: true,
        locked: false,
        sortable: false,
        order: 2,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'division',
        label: 'Division',
        visible: true,
        locked: false,
        sortable: false,
        order: 3,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'status',
        label: 'Status',
        visible: true,
        locked: false,
        sortable: false,
        order: 4,
        width: '120px',
        alignment: 'left',
        formatter: 'badge',
      },
      {
        key: 'last_update',
        label: 'Last Update',
        visible: true,
        locked: false,
        sortable: false,
        order: 5,
        width: '180px',
        alignment: 'left',
        formatter: 'datetime',
      },
      {
        key: 'actions',
        label: 'Actions',
        visible: true,
        locked: true,
        sortable: false,
        order: 6,
        width: '150px',
        alignment: 'right',
      },
    ],
  },

  /**
   * Buildings Index Table
   */
  buildings: {
    storageKey: 'buildings-column-preferences',
    columns: [
      {
        key: 'code',
        label: 'Code',
        visible: true,
        locked: true,
        sortable: true,
        order: 0,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'description',
        label: 'Description',
        visible: true,
        locked: false,
        sortable: false,
        order: 1,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'site',
        label: 'Site',
        visible: true,
        locked: false,
        sortable: false,
        order: 2,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'created',
        label: 'Created',
        visible: true,
        locked: false,
        sortable: true,
        order: 3,
        width: '180px',
        alignment: 'left',
        formatter: 'date',
      },
      {
        key: 'actions',
        label: 'Actions',
        visible: true,
        locked: true,
        sortable: false,
        order: 4,
        width: '120px',
        alignment: 'right',
      },
    ],
  },

  /**
   * Locations Index Table
   */
  locations: {
    storageKey: 'locations-column-preferences',
    columns: [
      {
        key: 'code',
        label: 'Code',
        visible: true,
        locked: true,
        sortable: true,
        order: 0,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'description',
        label: 'Description',
        visible: true,
        locked: false,
        sortable: false,
        order: 1,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'site',
        label: 'Site',
        visible: true,
        locked: false,
        sortable: false,
        order: 2,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'building',
        label: 'Building',
        visible: true,
        locked: false,
        sortable: false,
        order: 3,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'created',
        label: 'Created',
        visible: true,
        locked: false,
        sortable: true,
        order: 4,
        width: '180px',
        alignment: 'left',
        formatter: 'date',
      },
      {
        key: 'actions',
        label: 'Actions',
        visible: true,
        locked: true,
        sortable: false,
        order: 5,
        width: '120px',
        alignment: 'right',
      },
    ],
  },

  /**
   * Gateways Index Table
   */
  gateways: {
    storageKey: 'gateways-column-preferences',
    columns: [
      {
        key: 'checkbox',
        label: 'Select',
        visible: true,
        locked: true,
        sortable: false,
        order: 0,
        width: '48px',
        alignment: 'center',
      },
      {
        key: 'serial_number',
        label: 'Serial Number',
        visible: true,
        locked: true,
        sortable: true,
        order: 1,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'site',
        label: 'Site',
        visible: true,
        locked: false,
        sortable: false,
        order: 2,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'mac_address',
        label: 'MAC Address',
        visible: true,
        locked: false,
        sortable: false,
        order: 3,
        width: '150px',
        alignment: 'left',
      },
      {
        key: 'ip_address',
        label: 'IP Address',
        visible: true,
        locked: false,
        sortable: false,
        order: 4,
        width: '140px',
        alignment: 'left',
      },
      {
        key: 'status',
        label: 'Status',
        visible: true,
        locked: false,
        sortable: false,
        order: 5,
        width: '120px',
        alignment: 'left',
        formatter: 'badge',
      },
      {
        key: 'last_update',
        label: 'Last Update',
        visible: true,
        locked: false,
        sortable: false,
        order: 6,
        width: '180px',
        alignment: 'left',
        formatter: 'datetime',
      },
      {
        key: 'actions',
        label: 'Actions',
        visible: true,
        locked: true,
        sortable: false,
        order: 7,
        width: '150px',
        alignment: 'right',
      },
    ],
  },

  /**
   * Meters Index Table
   */
  meters: {
    storageKey: 'meters-column-preferences',
    columns: [
      {
        key: 'checkbox',
        label: 'Select',
        visible: true,
        locked: true,
        sortable: false,
        order: 0,
        width: '48px',
        alignment: 'center',
      },
      {
        key: 'name',
        label: 'Name',
        visible: true,
        locked: true,
        sortable: true,
        order: 1,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'type',
        label: 'Type',
        visible: true,
        locked: false,
        sortable: false,
        order: 2,
        width: '120px',
        alignment: 'left',
      },
      {
        key: 'brand',
        label: 'Brand',
        visible: true,
        locked: false,
        sortable: false,
        order: 3,
        width: '120px',
        alignment: 'left',
      },
      {
        key: 'customer',
        label: 'Customer',
        visible: true,
        locked: false,
        sortable: false,
        order: 4,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'site',
        label: 'Site',
        visible: true,
        locked: false,
        sortable: false,
        order: 5,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'gateway',
        label: 'Gateway',
        visible: true,
        locked: false,
        sortable: false,
        order: 6,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'status',
        label: 'Status',
        visible: true,
        locked: false,
        sortable: false,
        order: 7,
        width: '120px',
        alignment: 'left',
        formatter: 'badge',
      },
      {
        key: 'actions',
        label: 'Actions',
        visible: true,
        locked: true,
        sortable: false,
        order: 8,
        width: '150px',
        alignment: 'right',
      },
    ],
  },

  /**
   * Configuration Files Index Table
   */
  configFiles: {
    storageKey: 'config-files-column-preferences',
    columns: [
      {
        key: 'meter_model',
        label: 'Meter Model',
        visible: true,
        locked: true,
        sortable: true,
        order: 0,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'meters_using',
        label: 'Meters Using',
        visible: true,
        locked: false,
        sortable: false,
        order: 1,
        width: '140px',
        alignment: 'center',
      },
      {
        key: 'created',
        label: 'Created',
        visible: true,
        locked: false,
        sortable: true,
        order: 2,
        width: '180px',
        alignment: 'left',
        formatter: 'date',
      },
      {
        key: 'actions',
        label: 'Actions',
        visible: true,
        locked: true,
        sortable: false,
        order: 3,
        width: '120px',
        alignment: 'right',
      },
    ],
  },

  /**
   * Users Index Table
   */
  users: {
    storageKey: 'users-column-preferences',
    columns: [
      {
        key: 'name',
        label: 'Name',
        visible: true,
        locked: true,
        sortable: true,
        order: 0,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'email',
        label: 'Email',
        visible: true,
        locked: true,
        sortable: true,
        order: 1,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'job_title',
        label: 'Job Title',
        visible: true,
        locked: false,
        sortable: false,
        order: 2,
        width: 'auto',
        alignment: 'left',
      },
      {
        key: 'role',
        label: 'Role',
        visible: true,
        locked: false,
        sortable: false,
        order: 3,
        width: '120px',
        alignment: 'left',
      },
      {
        key: 'access_level',
        label: 'Access Level',
        visible: true,
        locked: false,
        sortable: false,
        order: 4,
        width: '140px',
        alignment: 'center',
      },
      {
        key: 'sites',
        label: 'Sites',
        visible: true,
        locked: false,
        sortable: false,
        order: 5,
        width: '100px',
        alignment: 'center',
      },
      {
        key: 'status',
        label: 'Status',
        visible: true,
        locked: false,
        sortable: false,
        order: 6,
        width: '120px',
        alignment: 'left',
        formatter: 'badge',
      },
      {
        key: 'actions',
        label: 'Actions',
        visible: true,
        locked: true,
        sortable: false,
        order: 7,
        width: '120px',
        alignment: 'right',
      },
    ],
  },
}

/**
 * Get table configuration by name
 */
export function getTableConfig(tableName: string): TableConfig | undefined {
  return tableConfigs[tableName]
}

/**
 * Get all table configurations
 */
export function getAllTableConfigs(): Record<string, TableConfig> {
  return tableConfigs
}

/**
 * Export for direct access
 */
export default tableConfigs
