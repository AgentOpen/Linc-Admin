import { reactive, watch } from 'vue'
import { DEFAULT_SKIN, SKIN_KEYS } from '@/themes'

/* 外观设置：皮肤主题（多主题包）、浅色 / 深色 / 跟随系统、表格密度、导航轨折叠。持久化在本机浏览器。 */
export type ThemeMode = 'light' | 'dark' | 'auto'
export type Density = 'comfy' | 'regular' | 'compact'
export type Skin = (typeof SKIN_KEYS)[number]

export interface Appearance {
  skin: Skin
  theme: ThemeMode
  density: Density
  railCollapsed: boolean
}

const STORAGE_KEY = 'linc.appearance'
const DEFAULTS: Appearance = { skin: DEFAULT_SKIN as Skin, theme: 'light', density: 'regular', railCollapsed: false }

function load(): Appearance {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) {
      const saved = JSON.parse(raw) as Partial<Appearance>
      return {
        skin: SKIN_KEYS.includes(saved.skin as Skin) ? saved.skin as Skin : DEFAULTS.skin,
        theme: (['light', 'dark', 'auto'] as const).includes(saved.theme as ThemeMode) ? saved.theme as ThemeMode : DEFAULTS.theme,
        density: (['comfy', 'regular', 'compact'] as const).includes(saved.density as Density) ? saved.density as Density : DEFAULTS.density,
        railCollapsed: Boolean(saved.railCollapsed),
      }
    }
  } catch { /* 存储不可用时使用默认值 */ }
  return { ...DEFAULTS }
}

export const appearance = reactive<Appearance>(load())

const darkQuery = window.matchMedia('(prefers-color-scheme: dark)')

function resolvedTheme() {
  if (appearance.theme === 'auto') return darkQuery.matches ? 'dark' : 'light'
  return appearance.theme
}

function apply() {
  const root = document.documentElement
  root.dataset.skin = appearance.skin
  root.dataset.theme = resolvedTheme()
  root.dataset.density = appearance.density
}

watch(() => [appearance.skin, appearance.theme, appearance.density], apply, { immediate: true })
darkQuery.addEventListener('change', () => { if (appearance.theme === 'auto') apply() })

watch(appearance, (value) => {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(value)) } catch { /* 忽略 */ }
}, { deep: true })

/** 切换皮肤主题（主题中心调用），立即生效并持久化。 */
export function useSkin() {
  function applySkin(key: Skin) {
    if (!SKIN_KEYS.includes(key)) return
    appearance.skin = key
  }
  return { appearance, applySkin }
}
