<script setup lang="ts">
/* 主题中心：多主题舞台 + 门户站点设置。点击即换、本机记住、门户前台实时跟随。 */
import { computed, onMounted, ref } from 'vue'
import Icon from '@/components/Icon.vue'
import { Button, Tag, TextInput } from '@/components/ui'
import { PageHeader } from '@/components/ui'
import { SKINS, skinOf } from '@/themes'
import { useSkin } from '@/composables/appearance'
import { pushToast, toastError } from '@/composables/toast'
import { APP } from '@/data'
import { revealCascade, settlePop } from '@/composables/motion'
import { portalApi } from '@/api'

const { appearance, applySkin } = useSkin()
const syncing = ref(false)
const savingColor = ref(false)

const activeSkin = computed(() => skinOf(appearance.skin))
const rootEl = ref<HTMLElement | null>(null)

/* 门户站点主色：预设色卡 + 自定义 */
const PRESET_COLORS = [
  { hex: '', label: '默认' },
  { hex: '#5e7ce0', label: '靛蓝' },
  { hex: '#236de9', label: '天蓝' },
  { hex: '#0fa37f', label: '青绿' },
  { hex: '#7a5cd6', label: '紫罗兰' },
  { hex: '#e37e29', label: '橙' },
  { hex: '#e0443b', label: '红' },
]
const portalColor = ref('')
const portalColorDirty = ref(false)

onMounted(async () => {
  revealCascade(rootEl.value)
  // 读取服务端当前主色
  try {
    const data = await portalApi.skin.get()
    if (data.primary) { portalColor.value = data.primary; portalColorDirty.value = false }
  } catch { /* 静默 */ }
})

function apply(key: string, name: string) {
  if (key === appearance.skin) return
  applySkin(key as never)
  pushToast(`已切换到「${name}」主题`, 'ok')
  settlePop(document.querySelector('.theme-grid'))
  syncing.value = true
  import('@/api').then(({ portalApi }) =>
    portalApi.skin.apply(key, portalColor.value || undefined)
      .catch(() => { /* 静默：本机主题仍生效 */ })
      .finally(() => { syncing.value = false })
  )
}

async function saveColor() {
  if (!portalColorDirty.value) return
  savingColor.value = true
  try {
    await portalApi.skin.apply(appearance.skin, portalColor.value || undefined)
    pushToast(portalColor.value ? '门户站点主色已保存' : '门户站点主色已恢复默认', 'ok')
    portalColorDirty.value = false
  } catch (error) {
    toastError(error, '主色保存失败')
  } finally {
    savingColor.value = false
  }
}

function setPreset(hex: string) {
  portalColor.value = hex
  portalColorDirty.value = true
}

function onCustomColor(e: Event) {
  portalColor.value = (e.target as HTMLInputElement).value
  portalColorDirty.value = true
}

function isActive(key: string) {
  return key === appearance.skin
}
</script>

<template>
  <div ref="rootEl" class="theme-page">
    <PageHeader title="主题中心" :desc="`为「${APP.name}」挑选外观皮肤：切换立即生效，门户前台实时跟随；浅色 / 深色与密度设置对所有主题通用。`">
      <Tag tone="info">已安装 {{ SKINS.length }} 套主题</Tag>
    </PageHeader>

    <!-- 当前主题舞台 -->
    <section class="stage-hero m-surface" :data-hero="activeSkin?.key">
      <div class="stage-glow" aria-hidden="true"></div>
      <div class="stage-info">
        <span class="stage-eyebrow">当前主题 · {{ activeSkin?.tagline }}</span>
        <div class="stage-name">
          {{ activeSkin?.name }}
          <span class="mono stage-key">{{ activeSkin?.key }} · v{{ activeSkin?.version }}</span>
        </div>
        <p class="stage-desc">{{ activeSkin?.description }}</p>
        <div class="stage-chips">
          <Tag v-for="tag in activeSkin?.tags" :key="tag" tone="neutral">{{ tag }}</Tag>
        </div>
      </div>
      <div class="stage-badge" aria-hidden="true">
        <Icon name="check" :size="14" />
        使用中
      </div>
    </section>

    <!-- 主题画廊 -->
    <section class="theme-grid" aria-label="已安装主题">
      <article
        v-for="skin in SKINS"
        :key="skin.key"
        :class="['theme-card m-surface', { 'is-active': isActive(skin.key) }]"
        :aria-label="`主题：${skin.name}`"
      >
        <div class="theme-shot" :style="{ background: skin.preview.canvas }">
          <div class="shot-rail" :style="{ background: skin.preview.rail }">
            <span class="shot-dot" :style="{ background: skin.preview.primary }"></span>
            <span class="shot-line shot-anim" :style="{ background: skin.preview.primary, opacity: .55, width: '62%' }"></span>
            <span class="shot-line shot-anim shot-d1" :style="{ background: skin.preview.primary, opacity: .28, width: '46%' }"></span>
            <span class="shot-line shot-anim shot-d2" :style="{ background: skin.preview.primary, opacity: .28, width: '52%' }"></span>
          </div>
          <div class="shot-main">
            <div class="shot-topbar" :style="{ background: skin.preview.surface }">
              <span class="shot-pill" :style="{ background: skin.preview.primary }"></span>
              <span class="shot-line shot-anim" :style="{ background: skin.preview.primary, opacity: .3, width: '30%' }"></span>
            </div>
            <div class="shot-cards">
              <div class="shot-card" :style="{ background: skin.preview.surface }">
                <span class="shot-num" :style="{ color: skin.preview.primary }">128</span>
                <span class="shot-line shot-anim" :style="{ background: skin.preview.primary, opacity: .24, width: '70%' }"></span>
              </div>
              <div class="shot-card" :style="{ background: skin.preview.surface }">
                <span class="shot-num" :style="{ color: skin.preview.accent2 }">96</span>
                <span class="shot-line shot-anim shot-d1" :style="{ background: skin.preview.accent2, opacity: .34, width: '56%' }"></span>
              </div>
              <div class="shot-chart" :style="{ background: skin.preview.surface }">
                <span
                  v-for="(h, i) in [46, 72, 34, 88, 60, 40]"
                  :key="i"
                  class="shot-bar shot-bar-anim"
                  :style="{ height: `${h}%`, background: skin.preview.primary, opacity: i === 3 ? 1 : .32, animationDelay: `${i * 0.18}s` }"
                ></span>
              </div>
            </div>
          </div>
          <span v-if="isActive(skin.key)" class="shot-badge">
            <Icon name="check" :size="12" />使用中
          </span>
          <span v-else-if="skin.default" class="shot-badge is-default">出厂默认</span>
        </div>

        <div class="theme-meta">
          <div class="theme-title">
            <h2>{{ skin.name }}</h2>
            <span class="mono faint">{{ skin.key }} · v{{ skin.version }}</span>
          </div>
          <p class="theme-tagline">{{ skin.tagline }}</p>
          <p class="theme-desc">{{ skin.description }}</p>
          <div class="theme-foot">
            <span class="faint mono">作者：{{ skin.author }}</span>
            <Button v-if="!isActive(skin.key)" size="sm" :disabled="syncing" @click="apply(skin.key, skin.name)">使用此主题</Button>
            <Button v-else size="sm" variant="secondary" disabled>
              <Icon name="check" :size="14" />当前主题
            </Button>
          </div>
        </div>
      </article>
    </section>

    <!-- 门户站点设置 -->
    <section class="portal-set m-surface reveal">
      <div class="portal-set-head">
        <b>门户站点设置</b>
        <span class="faint">门户前台外观与 SEO 文案；切换立即生效</span>
      </div>
      <div class="portal-set-body">
        <div class="portal-set-row">
          <span class="portal-set-label">站点主色</span>
          <div class="portal-color-row">
            <button
              v-for="c in PRESET_COLORS"
              :key="c.hex"
              :class="['color-swatch', { 'is-active': portalColor === c.hex }]"
              :style="{ background: c.hex || 'linear-gradient(135deg,#5e7ce0,#236de9)' }"
              :title="c.label"
              @click="setPreset(c.hex)"
            ></button>
            <input type="color" class="color-picker" :value="portalColor || '#5e7ce0'" @input="onCustomColor" title="自定义主色" />
          </div>
          <Button size="sm" variant="primary" :disabled="!portalColorDirty || savingColor" @click="saveColor">
            {{ savingColor ? '保存中…' : '保存主色' }}
          </Button>
        </div>
        <div class="portal-set-row">
          <span class="portal-set-label">SEO 文案</span>
          <span class="portal-set-note">
            在「参数配置」中编辑 <code class="mono">sys.seo.title</code>、<code class="mono">sys.seo.description</code>、<code class="mono">sys.seo.keywords</code> 三项参数，门户前台与搜索引擎 / AI 引擎（GEO）收录将实时使用。
          </span>
        </div>
      </div>
    </section>

    <footer class="theme-footnote">
      想开发自己的主题？令牌覆盖 + 一条注册表即可，教程见 <code class="mono">docs/05-扩展开发实战.md</code>（Linc-Admin-Dev 版）。
      门户前台外观将实时跟随此处的选择。
    </footer>
  </div>
</template>
