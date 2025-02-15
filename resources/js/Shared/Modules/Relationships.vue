<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <UsersRound class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Relationships') }} </span>
      </div>
      <pretty-link :text="$t('Add a relationship')" :icon="'plus'" :href="data.url.create" :class="'w-full sm:w-fit'" />
    </div>

    <!-- relationships -->
    <div>
      <div v-for="relationshipGroupType in localRelationships" :key="relationshipGroupType.id" class="mb-4">
        <!-- group name -->
        <h3 v-if="relationshipGroupType.relationship_types.length > 0" class="mb-1 font-semibold">
          {{ relationshipGroupType.name }}
        </h3>

        <!-- list of relationship types in this group -->
        <ul
          v-if="relationshipGroupType.relationship_types.length > 0"
          class="mb-4 rounded-lg border border-gray-200 last:mb-0 dark:border-gray-700">
          <li
            v-for="relationshipType in relationshipGroupType.relationship_types"
            :key="relationshipType.id"
            class="item-list flex items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            <div class="flex">
              <div class="me-2 flex items-center">
                <avatar :data="relationshipType.contact.avatar" :class="'me-2 h-5 w-5'" />

                <!-- name -->
                <InertiaLink
                  v-if="relationshipType.contact.url.show"
                  :href="relationshipType.contact.url.show"
                  class="text-blue-500 hover:underline">
                  {{ relationshipType.contact.name }}
                </InertiaLink>
                <span v-else>{{ relationshipType.contact.name }}</span>

                <!-- age -->
                <span v-if="relationshipType.contact.age" class="ms-2 text-xs text-gray-400"
                  >({{ relationshipType.contact.age }})</span
                >
              </div>

              <!-- relationship type -->
              <span class="me-2 text-gray-400">{{ relationshipType.relationship_type.name }}</span>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(relationshipType)">
                {{ $t('Remove') }}
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>

    <!-- blank state -->
    <div
      v-if="data.number_of_defined_relations === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_relationship.svg" :alt="$t('Relationships')" class="mx-auto mt-4 h-14 w-14" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no relationships yet.') }}</p>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import Avatar from '@/Shared/Avatar.vue';
import { UsersRound } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    PrettyLink,
    Avatar,
    UsersRound,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      localRelationships: [],
    };
  },

  created() {
    this.localRelationships = this.data.relationship_group_types;
  },

  methods: {
    destroy(relationshipType) {
      if (confirm(this.$t('Are you sure? This action cannot be undone.'))) {
        axios
          .put(relationshipType.url.update)
          .then((response) => {
            this.flash(this.$t('The relationship has been deleted'), 'success');
            this.localRelationships = response.data.data.relationship_group_types;
          })
          .catch((error) => {
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
