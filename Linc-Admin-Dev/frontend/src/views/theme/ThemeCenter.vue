<script setup lang="ts">
/* 主题中心：多主题舞台。点击即换、本机记住、门户前台实时跟随；主题包为 CSS 令牌覆盖。 */
import { computed, onMounted, ref } from 'vue'
import Icon from '@/components/Icon.vue'
import { Button, Tag } from '@/components/ui'
import { PageHeader } from '@/components/ui'
import { SKINS, skinOf } from '@/themes'
import { useSkin } from '@/composables/appearance'
import { pushToast } from '@/composables/toast'
import { APP } from '@/data'
import { revealCascade, settlePop } from '@/composables/motion'

const { appearance, applySkin } = useSkin()
const syncing = ref(false)

const activeSkin = computed(() => skinOf(appearance.skin))
const rootEl = ref<HTMLElement | null>(null)

onMounted(() => {
  revealCascade(rootEl.value)
})

function apply(key: string, name: string) {
  if (key === appearance.skin) return
  applySkin(key as never)
  pushToast(`已切换到「${name}」主题`, 'ok')
  settlePop(document.querySelector('.theme-grid'))
  // 持久化到服务端：门户前台实时跟随（无权限或接口异常时保持本机切换，静默降级）
  syncing.value = true
  import('@/api').then(({ portalApi }) =>
    portalApi.skin.apply(key)
      .catch(() => { /* 静默：本机主题仍生效 */ })
      .finally(() => { syncing.value = false })
  )
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

    <!-- 当前主题舞台：渐变流动横幅 -->
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

    <footer class="theme-footnote">
      想开发自己的主题？令牌覆盖 + 一条注册表即可，教程见 <code class="mono">docs/05-扩展开发实战.md</code>（Linc-Admin-Dev 版）。
      门户前台外观将实时跟随此处的选择。
    </footer>
  </div>
</template>
