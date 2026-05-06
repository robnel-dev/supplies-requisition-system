import { ref } from 'vue'

const toast = ref({
  show: false,
  message: '',
  type: 'success', // 'success' | 'error'
})

export function useToast() {
  const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type }
    setTimeout(() => {
      toast.value.show = false
    }, 3000)
  }

  const hideToast = () => {
    toast.value.show = false
  }

  return { toast, showToast, hideToast }
}