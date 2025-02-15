<template>
  <div class="mb-4">
    <div class="pb-1 mb-2 items-center justify-between border-b border-gray-200 dark:border-gray-700 flex">
      <div class="text-xs">{{ $t('Name') }}</div>
      <InertiaLink :href="data.url.edit" class="relative">
        <Pencil class="h-3 w-3 text-gray-400" />
      </InertiaLink>
    </div>

    <h1 class="flex items-center justify-between text-xl font-bold">
      <span class="me-2">
        {{ data.name }}
      </span>

      <!-- if the contact is not yet a favorite -->
      <a-tooltip
        placement="topLeft"
        :title="$t('Set as favorite')"
        arrow-point-at-center
        @click.prevent="toggleFavorite">
        <StarIcon :is-favorite="localData.is_favorite" @click.prevent="toggleFavorite" />
      </a-tooltip>
    </h1>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import { Tooltip as ATooltip } from 'ant-design-vue';
import StarIcon from '@/Shared/Icons/StarIcon.vue';
import { Pencil } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    ATooltip,
    StarIcon,
    Pencil,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      localData: [],
    };
  },

  created() {
    this.localData = this.data;
  },

  methods: {
    toggleFavorite() {
      axios
        .put(this.data.url.toggle_favorite)
        .then((response) => {
          this.flash(this.$t('Changes saved'), 'success');
          this.localData = response.data.data;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>
