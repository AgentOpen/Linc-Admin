<script setup lang="ts">
/* 主题中心：多主题图库。点击即换、本机记住；主题包为 CSS 令牌覆盖，见 docs/05-扩展开发实战。 */
import { computed } from 'vue'
import Icon from '@/components/Icon.vue'
import { Button, SectionLabel, Tag } from '@/components/ui'
import { PageHeader } from '@/components/ui'
import { SKINS } from '@/themes'
import { useSkin } from '@/composables/appearance'
import { pushToast } from '@/composables/toast'
import { APP } from '@/data'
import { settlePop } from '@/composables/motion'

const { appearance, applySkin } = useSkin()

const FEATURES = [
  { icon: 'shield', title: '认证与权限', desc: 'JWT 会话 + 菜单按钮两级权限码，超级管理员直通' },
  { icon: 'dept', title: '组织与数据权限', desc: '部门 / 岗位 / 角色，数据范围五种粒度' },
  { icon: 'log', title: '全程审计', desc: '操作日志与登录日志，趋势、泳道、热力与异常聚合' },
  { icon: 'command', title: '代码生成', desc: '导入数据表，预览并下载业务模块开发骨架' },
  { icon: 'theme', title: '多主题', desc: '令牌级主题包，新建主题无需改动任何业务页面' },
  { icon: 'dashboard', title: '工作台', desc: '读数、登录脉搏、当日泳道，一屏掌握系统动态' },
]

const activeSkin = computed(() => appearance.skin)

function apply(key: string, name: string) {
  if (key === activeSkin.value) return
  applySkin(key as never)
  pushToast(`已切换到「${name}」主题`, 'ok')
  settlePop(document.querySelector('.theme-grid'))
}

function isActive(key: string) {
  return key === activeSkin.value
}
</script>

<template>
  <div class="theme-page">
    <PageHeader title="主题中心" :desc="`为「${APP.name}」挑选外观皮肤：切换立即生效、按浏览器记忆，浅色 / 深色与密度设置对所有主题通用。`">
      <Tag tone="info">已安装 {{ SKINS.length }} 套主题</Tag>
    </PageHeader>

    <!-- 主题图库 -->
    <section class="theme-grid" aria-label="已安装主题">
      <article
        v-for="skin in SKINS"
        :key="skin.key"
        :class="['theme-card m-surface', { 'is-active': isActive(skin.key) }]"
        :aria-label="`主题：${skin.name}`"
      >
        <div class="theme-shot" :style="{ background: skin.preview.canvas }">
          <!-- 迷你布局预览：按主题预览色卡绘制 -->
          <div class="shot-rail" :style="{ background: skin.preview.rail }">
            <span class="shot-dot" :style="{ background: skin.preview.primary }" />
            <span class="shot-line" :style="{ background: skin.preview.primary, opacity: .55, width: '62%' }" />
            <span class="shot-line" :style="{ background: skin.preview.primary, opacity: .28, width: '46%' }" />
            <span class="shot-line" :style="{ background: skin.preview.primary, opacity: .28, width: '52%' }" />
          </div>
          <div class="shot-main">
            <div class="shot-topbar" :style="{ background: skin.preview.surface }">
              <span class="shot-pill" :style="{ background: skin.preview.primary }" />
              <span class="shot-line" :style="{ background: skin.preview.primary, opacity: .3, width: '30%' }" />
            </div>
            <div class="shot-cards">
              <div class="shot-card" :style="{ background: skin.preview.surface }">
                <span class="shot-num" :style="{ color: skin.preview.primary }">128</span>
                <span class="shot-line" :style="{ background: skin.preview.primary, opacity: .24, width: '70%' }" />
              </div>
              <div class="shot-card" :style="{ background: skin.preview.surface }">
                <span class="shot-num" :style="{ color: skin.preview.accent2 }">96</span>
                <span class="shot-line" :style="{ background: skin.preview.accent2, opacity: .34, width: '56%' }" />
              </div>
              <div class="shot-chart" :style="{ background: skin.preview.surface }">
                <span
                  v-for="(h, i) in [46, 72, 34, 88, 60, 40]"
                  :key="i"
                  class="shot-bar"
                  :style="{ height: `${h}%`, background: i === 3 ? skin.preview.primary : skin.preview.primary, opacity: i === 3 ? 1 : .35 }"
                />
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
          <div class="theme-tags">
            <Tag v-for="tag in skin.tags" :key="tag" tone="neutral">{{ tag }}</Tag>
          </div>
          <div class="theme-foot">
            <span class="faint mono">作者：{{ skin.author }}</span>
            <Button v-if="!isActive(skin.key)" size="sm" @click="apply(skin.key, skin.name)">使用此主题</Button>
            <Button v-else size="sm" variant="secondary" disabled>
              <Icon name="check" :size="14" />当前主题
            </Button>
          </div>
        </div>
      </article>
    </section>

    <!-- 系统能力速览：围绕当前后台管理系统的介绍 -->
    <section class="theme-intro m-surface">
      <SectionLabel title="这套后台能做什么" as="h2" />
      <p class="theme-intro-lead">
        {{ APP.name }}（{{ APP.subtitle }}）是一套开箱即用的中后台底座：安装向导三步完成部署，业务模块按统一组件与权限规范生长，外观由主题包驱动。
      </p>
      <div class="feature-grid">
        <div v-for="f in FEATURES" :key="f.title" class="feature-item">
          <span class="feature-ico"><Icon :name="f.icon" :size="17" /></span>
          <div>
            <p class="feature-title">{{ f.title }}</p>
            <p class="feature-desc">{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 主题开发指引 -->
    <section class="theme-dev m-surface">
      <div>
        <SectionLabel title="想做一个自己的主题？" as="h2" />
        <p class="theme-intro-lead">
          主题 = 一份 CSS 令牌覆盖 + 一条注册表登记。全站颜色、圆角、阴影、材质都来自设计令牌，
          新建主题不需要改动任何业务页面；完整步骤见
          <code class="mono">docs/05-扩展开发实战.md</code> 的「主题开发」章节。
        </p>
      </div>
      <ol class="dev-steps">
        <li><span class="mono faint">01</span>复制 <code class="mono">styles/themes/linc.css</code> 为新文件，改写令牌取值</li>
        <li><span class="mono faint">02</span>在 <code class="mono">src/themes/index.ts</code> 的 <code class="mono">SKINS</code> 登记元数据与预览色卡</li>
        <li><span class="mono faint">03</span>在 <code class="mono">main.ts</code> 引入新样式文件，重新构建前端</li>
        <li><span class="mono faint">04</span>主题中心即可看到并切换新主题</li>
      </ol>
    </section>
  </div>
</template>