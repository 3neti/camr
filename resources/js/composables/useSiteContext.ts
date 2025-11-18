import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Access the selected site context from Laravel session
 * 
 * This composable provides reactive access to the site ID that was selected
 * and stored in the Laravel session by the ManageSiteContext middleware.
 * 
 * @returns {Object} Object containing:
 *   - selectedSiteId: The currently selected site ID (number | null)
 *   - hasSelectedSite: Whether a site is currently selected (boolean)
 */
export function useSiteContext() {
  const page = usePage()

  const selectedSiteId = computed<number | null>(() => {
    return page.props.selectedSiteId as number | null
  })

  const hasSelectedSite = computed(() => {
    return selectedSiteId.value !== null && selectedSiteId.value !== undefined
  })

  return {
    selectedSiteId,
    hasSelectedSite,
  }
}
