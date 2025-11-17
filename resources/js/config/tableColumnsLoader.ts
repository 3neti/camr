/**
 * Table Columns Configuration Loader
 * 
 * This module handles loading the table column configuration with automatic
 * fallback to the example file if the environment-specific config is missing.
 * 
 * This ensures the application works on fresh installations without manual setup.
 * 
 * Usage:
 *   import { getTableConfig } from '@/config/tableColumnsLoader'
 *   const config = getTableConfig('sites')
 */

import exampleConfig from './tableColumns.example'
import type { TableConfig, ColumnConfig } from './tableColumns.example'

// Use Vite's import.meta.glob to conditionally load config files
const configModules = import.meta.glob('./tableColumns.ts', { eager: true })

// Determine which config to use
let tableConfigs: Record<string, TableConfig>
let isUsingExampleConfig = false

if (configModules['./tableColumns.ts']) {
  // Custom config exists, use it
  tableConfigs = (configModules['./tableColumns.ts'] as any).default
  isUsingExampleConfig = false
  
  if (import.meta.env.DEV) {
    console.info('[TableColumns] ✓ Using environment-specific configuration')
  }
} else {
  // Use example config as fallback
  tableConfigs = exampleConfig
  isUsingExampleConfig = true
  
  if (import.meta.env.DEV) {
    console.warn(
      '[TableColumns] ⚠ Using example configuration.\n' +
      'For production, copy tableColumns.example.ts to tableColumns.ts and customize it.'
    )
  }
}

/**
 * Get table configuration by name
 * @param tableName - Name of the table (e.g., 'sites', 'users')
 * @returns Table configuration or undefined if not found
 * 
 * @example
 * const sitesConfig = getTableConfig('sites')
 * if (sitesConfig) {
 *   console.log(sitesConfig.columns)
 * }
 */
export function getTableConfig(tableName: string): TableConfig | undefined {
  return tableConfigs[tableName]
}

/**
 * Get all table configurations
 * @returns All table configurations as a record
 * 
 * @example
 * const allConfigs = getAllTableConfigs()
 * Object.keys(allConfigs).forEach(tableName => {
 *   console.log(`Table: ${tableName}`)
 * })
 */
export function getAllTableConfigs(): Record<string, TableConfig> {
  return tableConfigs
}

/**
 * Check if currently using the example config (not customized)
 * Useful for displaying warnings in admin panels or setup wizards
 * @returns true if using example config, false if using custom config
 * 
 * @example
 * if (isExampleConfig()) {
 *   showSetupWarning('Please customize your table column configuration')
 * }
 */
export function isExampleConfig(): boolean {
  return isUsingExampleConfig
}

/**
 * Export types for convenience
 */
export type { TableConfig, ColumnConfig }

/**
 * Re-export default for backward compatibility
 */
export default tableConfigs
