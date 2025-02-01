<template>
  <div class="mb-4">
    <div class="mb-1 items-center justify-between border-b border-gray-200 dark:border-gray-700 flex">
      <div class="mb-2 text-xs sm:mb-0">{{ $t('Name') }}</div>
      <InertiaLink :href="data.url.edit" class="relative">
        <EditIcon />
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
import EditIcon from '@/Shared/Icons/EditIcon.vue';
import StarIcon from '@/Shared/Icons/StarIcon.vue';

export default {
  components: {
    InertiaLink: Link,
    ATooltip,
    EditIcon,
    StarIcon,
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

<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}
</style>
