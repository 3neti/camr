import { usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import { toast } from 'vue-sonner'

export function useFlash() {
  const page = usePage()

  // Watch for flash messages and display toasts
  watch(
    () => page.props.flash,
    (flash) => {
      if (!flash) return

      if (flash.success) {
        toast.success(flash.success)
      }

      if (flash.error) {
        toast.error(flash.error)
      }

      if (flash.info) {
        toast.info(flash.info)
      }

      if (flash.warning) {
        toast.warning(flash.warning)
      }
    },
    { deep: true, immediate: true }
  )

  return {
    success: (message: string) => toast.success(message),
    error: (message: string) => toast.error(message),
    info: (message: string) => toast.info(message),
    warning: (message: string) => toast.warning(message),
  }
}
