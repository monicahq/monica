<script setup>
import PlusIcon from '@/Shared/Icons/PlusIcon.vue';
import CheckedIcon from '@/Shared/Icons/CheckedIcon.vue';
import MinusIcon from '@/Shared/Icons/MinusIcon.vue';
import ArrowIcon from '@/Shared/Icons/ArrowIcon.vue';
import { Trash2 } from 'lucide-vue-next';

defineProps({
  text: String,
  icon: String,
  state: String,
});
</script>

<template>
  <button
    :class="'dark:box-s relative border-zinc-900 bg-white text-sm dark:border-zinc-100 dark:bg-gray-800 dark:text-gray-100 flex'"
    :disabled="state === 'loading' || state === 'disabled'"
    type="submit">
    <span v-if="state === 'loading'">
      {{ $t('Loading…') }}
    </span>

    <template v-else>
      <PlusIcon v-if="icon === 'plus'" />

      <CheckedIcon v-else-if="icon === 'check'" />

      <MinusIcon v-else-if="icon === 'minus'" />

      <Trash2 class="icon relative me-1 inline h-5 w-5" v-else-if="icon === 'trash'" />

      <span>
        {{ text }}
      </span>

      <ArrowIcon :type="'right'" :size="'big'" v-if="icon === 'arrow'" />
    </template>
  </button>
</template>

<style lang="scss" scoped>
.icon {
  top: -1px;
}

.save {
  background-color: #fcf27e;
}

.dark .save {
  background-color: #d0c10d !important;
  color: rgb(31 41 55) !important; // text-gray-800
}

button {
  --tw-shadow: 2px 2px 0 #191a1b !important;
  border-radius: 0.25rem !important;
  border-width: 1px !important;
  box-shadow:
    var(--tw-ring-offset-shadow, 0 0 transparent), var(--tw-ring-shadow, 0 0 transparent), var(--tw-shadow) !important;
  display: inline-block !important;
  position: relative !important;
  text-decoration: none !important;
  transition-duration: 0.15s !important;
  transition-property:
    background-color,
    border-color,
    color,
    fill,
    stroke,
    opacity,
    box-shadow,
    transform,
    filter,
    backdrop-filter,
    -webkit-backdrop-filter !important;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1) !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
  padding-left: 9px;
  padding-right: 9px;

  &:hover {
    box-shadow: none !important;
    transform: translate(2px, 2px);
  }

  &:disabled {
    box-shadow: none;
    transform: translate(0, 0);
  }
}

.dark button {
  --tw-shadow: 2px 2px 0 rgb(242, 242, 245) !important;
}
</style>
