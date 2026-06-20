import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useOperatorAuthStore } from '@/stores/operator.auth.store'

const user = { id: 'u1', full_name: 'Chủ nhà xe', email: 'op@xeghep.vn', phone: '0912345678' }
const operator = { id: 'op-1', company_name: 'Xe Ghép Bắc Hà', status: 'verified', commission_rate: 10 }

describe('useOperatorAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('setAuth persists token, user and operator info', () => {
    const store = useOperatorAuthStore()

    store.setAuth('op-tok', user, operator)

    expect(store.isAuthenticated).toBe(true)
    expect(store.operator).toEqual(operator)
    expect(localStorage.getItem('operator_token')).toBe('op-tok')
    expect(JSON.parse(localStorage.getItem('operator_user')!)).toEqual(user)
    expect(JSON.parse(localStorage.getItem('operator_info')!)).toEqual(operator)
  })

  it('logout clears state and all persisted operator keys', () => {
    const store = useOperatorAuthStore()
    store.setAuth('op-tok', user, operator)

    store.logout()

    expect(store.isAuthenticated).toBe(false)
    expect(store.operator).toBeNull()
    for (const key of ['operator_token', 'operator_user', 'operator_info']) {
      expect(localStorage.getItem(key)).toBeNull()
    }
  })

  it('rehydrates operator info from localStorage on creation', () => {
    localStorage.setItem('operator_token', 'persisted')
    localStorage.setItem('operator_info', JSON.stringify(operator))

    const store = useOperatorAuthStore()

    expect(store.isAuthenticated).toBe(true)
    expect(store.operator?.company_name).toBe('Xe Ghép Bắc Hà')
  })
})
