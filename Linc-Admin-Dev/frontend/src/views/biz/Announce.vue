<script setup lang="ts">
import { reactive, ref } from 'vue'
import { Field, StatusTag, Switch, TextInput, type Column } from '@/components/ui'
import { FormDialog, ListPage, PermissionButton } from '@/components/business'
import { portalApi } from '@/api'
import { useListPage } from '@/composables/list'
import { confirm } from '@/composables/confirm'
import { pushToast, toastError } from '@/composables/toast'
import { required, validate } from '@/utils/validate'
import type { AnnounceRow } from '@/api/portal'

/* 公告管理：门户前台「公告动态」的内容源；发布后立即在前台展示。 */

const list = useListPage<AnnounceRow, { keyword: string }>({
  query: { keyword: '' },
  fetch: params => portalApi.announce.list(params) as Promise<any>,
})

const columns: Column[] = [
  { key: 'title', title: '公告标题', minWidth: 260 },
  { key: 'publishTime', title: '发布时间', width: 160 },
  { key: 'status', title: '状态', width: 90 },
  { key: 'actions', title: '操作', width: 110, className: 'col-actions' },
]

const dialog = ref(false)
const saving = ref(false)
const errors = ref<Partial<Record<'title', string>>>({})
const form = reactive<{ id?: number; title: string; content: string; status: number; publishTime: string }>({
  id: undefined, title: '', content: '', status: 1, publishTime: '',
})

function openCreate() {
  Object.assign(form, { id: undefined, title: '', content: '', status: 1, publishTime: '' })
  errors.value = {}
  dialog.value = true
}

function openEdit(row: AnnounceRow) {
  Object.assign(form, { id: row.id, title: row.title, content: row.content ?? '', status: row.status, publishTime: row.publishTime ?? '' })
  errors.value = {}
  dialog.value = true
}

async function submit() {
  errors.value = validate(form, {
    title: [required('请输入公告标题')],
  })
  if (Object.keys(errors.value).length) return
  saving.value = true
  const payload = { title: form.title.trim(), content: form.content, status: form.status, publishTime: form.publishTime || undefined }
  try {
    if (form.id) {
      await portalApi.announce.update(form.id, payload)
      pushToast('公告已更新')
    } else {
      await portalApi.announce.create(payload)
      pushToast('公告已发布')
    }
    dialog.value = false
    await list.load()
  } catch (error) {
    toastError(error, '公告保存失败')
  } finally {
    saving.value = false
  }
}

async function remove(row: AnnounceRow) {
  const ok = await confirm({ title: '删除公告', message: `确认删除公告「${row.title}」吗？前台将不再展示。`, confirmText: '删除', danger: true })
  if (!ok) return
  try {
    await portalApi.announce.remove(row.id)
    pushToast('公告已删除')
    await list.load()
  } catch (error) {
    toastError(error, '公告删除失败')
  }
}
</script>

<template>
  <ListPage title="公告管理" desc="门户前台「公告动态」的内容源：状态为发布即在前台展示，编辑与删除实时生效。" table-title="公告列表" :list="list" :columns="columns" :min-width="760">
    <template #actions>
      <PermissionButton perm="biz:announce:add" variant="primary" icon="plus" @click="openCreate">新增公告</PermissionButton>
    </template>

    <template #search>
      <Field label="关键词" wide>
        <TextInput v-model="list.query.keyword" icon="search" placeholder="标题 / 内容" clearable @enter="list.search()" />
      </Field>
    </template>

    <template #cell-title="{ row }">
      <div class="announce-title-cell">
        <b>{{ row.title }}</b>
        <span v-if="row.content" class="announce-excerpt">{{ row.content.slice(0, 46) }}{{ row.content.length > 46 ? '…' : '' }}</span>
      </div>
    </template>
    <template #cell-publishTime="{ row }"><span class="tnum soft">{{ row.publishTime }}</span></template>
    <template #cell-status="{ row }"><StatusTag :status="row.status" /></template>
    <template #cell-actions="{ row }">
      <div class="row-actions">
        <PermissionButton perm="biz:announce:edit" icon-only icon="edit" label="编辑" @click="openEdit(row)" />
        <PermissionButton perm="biz:announce:remove" icon-only icon="trash" label="删除" danger @click="remove(row)" />
      </div>
    </template>

    <FormDialog :open="dialog" :title="form.id ? '编辑公告' : '新增公告'" :saving="saving" @close="dialog = false" @submit="submit">
      <Field label="公告标题" required :error="errors.title">
        <TextInput v-model="form.title" placeholder="展示在前台的标题" :invalid="!!errors.title" :maxlength="200" />
      </Field>
      <Field label="公告内容" span>
        <TextInput v-model="form.content" multiline :rows="5" placeholder="正文内容，前台列表展示前 120 字摘要" />
      </Field>
      <Field label="发布时间" hint="留空则取创建时间；前台按发布时间倒序排列">
        <TextInput v-model="form.publishTime" placeholder="YYYY-MM-DD HH:mm" />
      </Field>
      <Field label="发布状态" group hint="草稿仅后台可见，前台不展示">
        <Switch :model-value="form.status === 1" :label="form.status === 1 ? '已发布' : '草稿'" @update:model-value="form.status = $event ? 1 : 0" />
      </Field>
    </FormDialog>
  </ListPage>
</template>

<style scoped>
.announce-title-cell { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.announce-title-cell b { font-weight: 600; }
.announce-excerpt { font-size: 12px; color: var(--ink-faint); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 380px; display: block; }
</style>
