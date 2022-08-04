<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 sm:flex">
      <div class="mb-2 sm:mb-0">
        <span class="relative mr-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-sidebar relative inline h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </span>

        <span class="font-semibold"> Relationships </span>
      </div>
      <pretty-link :text="'Add a relationship'" :icon="'plus'" :href="data.url.create" :classes="'sm:w-fit w-full'" />
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
          class="mb-4 rounded-lg border border-gray-200 last:mb-0">
          <li
            v-for="relationshipType in relationshipGroupType.relationship_types"
            :key="relationshipType.id"
            class="item-list flex items-center justify-between border-b border-gray-200 px-5 py-2 hover:bg-slate-50">
            <div class="flex">
              <div class="mr-2 flex items-center">
                <avatar :data="relationshipType.contact.avatar" :classes="'mr-2 h-5 w-5'" />

                <!-- name -->
                <inertia-link
                  v-if="relationshipType.contact.url.show"
                  :href="relationshipType.contact.url.show"
                  class="text-blue-500 hover:underline">
                  {{ relationshipType.contact.name }}
                </inertia-link>
                <span v-else>{{ relationshipType.contact.name }}</span>

                <!-- age -->
                <span v-if="relationshipType.contact.age" class="ml-2 text-xs text-gray-400"
                  >({{ relationshipType.contact.age }})</span
                >
              </div>

              <!-- relationship type -->
              <span class="mr-2 text-gray-400">{{ relationshipType.relationship_type.name }}</span>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(relationshipType)">
                Remove
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>

    <!-- blank state -->
    <div v-if="data.number_of_defined_relations == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">There are no relationships yet.</p>
    </div>
  </div>
</template>

<script>
import PrettyLink from '@/Shared/Form/PrettyLink';
import TextInput from '@/Shared/Form/TextInput';
import TextArea from '@/Shared/Form/TextArea';
import Errors from '@/Shared/Form/Errors';
import Avatar from '@/Shared/Avatar';

export default {
  components: {
    PrettyLink,
    TextInput,
    TextArea,
    Errors,
    Avatar,
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
      if (confirm('Are you sure? This will delete the relationship.')) {
        axios
          .put(relationshipType.url.update)
          .then((response) => {
            this.flash('The relationship has been deleted', 'success');
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
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

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
