import { router } from '@inertiajs/vue3'

export function useExport() {
  /**
   * Export data to CSV format
   * @param data - Array of objects to export
   * @param filename - Name of the CSV file
   * @param columns - Optional array of column configs [{key: 'name', label: 'Name'}]
   */
  function exportToCSV(
    data: Array<Record<string, any>>,
    filename: string,
    columns?: Array<{ key: string; label: string }>
  ) {
    if (data.length === 0) {
      alert('No data to export')
      return
    }

    // Determine columns from data if not provided
    const exportColumns = columns || Object.keys(data[0]).map(key => ({ key, label: key }))

    // Create CSV header
    const headers = exportColumns.map(col => col.label).join(',')

    // Create CSV rows
    const rows = data.map(item => {
      return exportColumns.map(col => {
        const value = getNestedValue(item, col.key)
        // Escape quotes and wrap in quotes if contains comma or newline
        const stringValue = String(value ?? '')
        if (stringValue.includes(',') || stringValue.includes('\n') || stringValue.includes('"')) {
          return `"${stringValue.replace(/"/g, '""')}"`
        }
        return stringValue
      }).join(',')
    }).join('\n')

    // Combine header and rows
    const csv = `${headers}\n${rows}`

    // Create blob and download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    
    link.setAttribute('href', url)
    link.setAttribute('download', `${filename}.csv`)
    link.style.visibility = 'hidden'
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }

  /**
   * Get nested object value using dot notation
   * e.g., 'site.code' returns item.site.code
   */
  function getNestedValue(obj: any, path: string): any {
    return path.split('.').reduce((current, key) => current?.[key], obj)
  }

  /**
   * Request CSV export from backend endpoint
   * Useful when backend needs to export all data (not just paginated)
   */
  function exportFromBackend(url: string, params: Record<string, any> = {}) {
    const queryString = new URLSearchParams(
      Object.entries(params).filter(([_, v]) => v !== undefined && v !== null)
    ).toString()
    
    const fullUrl = queryString ? `${url}?${queryString}` : url
    
    // Open in new tab to trigger download
    window.open(fullUrl, '_blank')
  }

  return {
    exportToCSV,
    exportFromBackend,
  }
}
