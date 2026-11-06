/* ============================================================
   主题注册表：后台多主题的唯一清单。
   新增主题 = ① styles/themes/<key>.css 提供令牌覆盖
             ② 在 SKINS 登记元数据（预览色卡驱动主题中心卡片）
   详见 docs/05-扩展开发实战.md 的「主题开发」章节。
   ============================================================ */

export interface SkinPreview {
  /** 主色（按钮 / 链接） */
  primary: string
  /** 画布底色 */
  canvas: string
  /** 面板底色 */
  surface: string
  /** 侧栏底色 */
  rail: string
  /** 辅助强调色（预览点缀） */
  accent2: string
}

export interface SkinMeta {
  key: string
  name: string
  tagline: string
  version: string
  author: string
  description: string
  tags: string[]
  preview: SkinPreview
  /** 出厂默认主题（仅一个） */
  default?: boolean
}

export const SKINS: SkinMeta[] = [
  {
    key: 'linc',
    name: '灵枢青',
    tagline: 'DevUI 风格 · 克制高效',
    version: '1.0.0',
    author: '灵枢中控',
    description:
      '出厂默认主题，复刻 DevUI 设计语言：靛蓝主色、冷灰画布、纯白面板与小圆角，扁平克制、信息密度友好，适合长时间值守的中后台作业。',
    tags: ['出厂默认', '扁平高效', '靛蓝主色', '小圆角'],
    preview: { primary: '#5e7ce0', canvas: '#f5f6f8', surface: '#ffffff', rail: '#ffffff', accent2: '#3dcc7e' },
    default: true,
  },
  {
    key: 'pearl',
    name: '珍珠玻璃',
    tagline: '玻璃拟态 · 轻盈通透',
    version: '1.0.0',
    author: '灵枢中控',
    description:
      '灵枢中控的经典主题：柔和灰绿画布、玻璃浮层与仪器化语汇，青柠点缀、层次柔和，适合偏好质感与视觉氛围的工作台场景。',
    tags: ['经典', '玻璃拟态', '青柠点缀', '仪器化'],
    preview: { primary: '#236de9', canvas: '#f6f8f7', surface: '#fbfcfc', rail: '#fafbfa', accent2: '#82dd2d' },
  },
]

export const DEFAULT_SKIN = SKINS.find(skin => skin.default)?.key ?? SKINS[0]?.key ?? 'linc'

export const SKIN_KEYS = SKINS.map(skin => skin.key)

export function skinOf(key: string | null | undefined): SkinMeta | undefined {
  return SKINS.find(skin => skin.key === key)
}
