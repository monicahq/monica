<style lang="scss" scoped>
.contact-list {
  li:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  li:last-child {
    border-bottom: 0;
  }

  li:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}

.special-grid {
  grid-template-columns: 200px 1fr;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div>
            <!-- labels -->
            <div>
              <div class="mb-3 border-b border-gray-200">
                <span class="mr-1">🏷️</span> {{ $t('vault.show_contacts_labels') }}
              </div>
              <ul v-if="data.labels.length > 0">
                <li class="mb-1">
                  <div v-if="data.current_label">
                    <inertia-link :href="data.url.contact.index" class="text-blue-500 hover:underline">{{
                      $t('app.view_all')
                    }}</inertia-link>
                  </div>
                  <div v-if="!data.current_label">{{ $t('app.view_all') }}</div>
                </li>
                <li v-for="label in data.labels" :key="label.id" class="mb-1">
                  <div v-if="label.id !== data.current_label">
                    <inertia-link :href="label.url.show" class="text-blue-500 hover:underline">{{
                      label.name
                    }}</inertia-link>
                    <span class="text-sm text-gray-500">({{ label.count }})</span>
                  </div>
                  <div v-if="label.id === data.current_label">
                    {{ label.name }} <span class="text-sm text-gray-500">({{ label.count }})</span>
                  </div>
                </li>
              </ul>

              <p v-else class="text-sm text-gray-500">{{ $t('vault.show_contacts_labels_blank') }}</p>
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <!-- title + cta -->
            <div class="mb-6 flex items-center justify-between">
              <h3><span class="mr-1"> 🥸 </span> {{ $t('vault.show_contacts_index') }}</h3>
              <pretty-link
                v-if="layoutData.vault.permission.at_least_editor"
                :href="data.url.contact.create"
                :text="$t('vault.show_contacts_cta')"
                :icon="'plus'" />
            </div>

            <!-- contact list -->
            <ul class="contact-list mb-6 rounded-lg border border-gray-200 bg-white">
              <li
                v-for="contact in data.contacts"
                :key="contact.id"
                class="flex items-center border-b border-gray-200 px-5 py-2 hover:bg-slate-50">
                <div v-html="contact.avatar" class="mr-2 h-5 w-5"></div>
                <inertia-link :href="contact.url.show" class="text-blue-500 hover:underline">
                  {{ contact.name }}
                </inertia-link>
              </li>
            </ul>

            <!-- pagination -->
            <div v-if="!moduleMode" class="flex justify-between text-center">
              <inertia-link
                v-show="paginator.previousPageUrl"
                class="fl dib"
                :href="paginator.previousPageUrl"
                title="Previous">
                &larr; {{ $t('app.previous') }}
              </inertia-link>
              <inertia-link v-show="paginator.nextPageUrl" class="fr dib" :href="paginator.nextPageUrl" title="Next">
                {{ $t('app.next') }} &rarr;
              </inertia-link>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyLink from '@/Shared/Form/PrettyLink';

export default {
  components: {
    Layout,
    PrettyLink,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    paginator: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {};
  },

  methods: {},
};
</script>
