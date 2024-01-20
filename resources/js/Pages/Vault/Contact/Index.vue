<script setup>
import { router, useForm, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import Layout from '@/Shared/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import Avatar from '@/Shared/Avatar.vue';
import Pagination from '@/Components/Pagination.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
  paginator: Object,
});

const form = useForm({
  sort_order: props.data.user_contact_sort_order,
});

const update = () => {
  axios.put(props.data.url.sort.update, form).then((response) => {
    localStorage.success = trans('Changes saved');
    router.visit(response.data.data);
  });
};
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div>
            <!-- labels -->
            <div class="mb-8">
              <div class="mb-3 border-b border-gray-200 dark:border-gray-700">
                <span class="me-1"> 🏷️ </span>
                {{ $t('Labels') }}
              </div>
              <ul v-if="data.labels.length > 0">
                <li class="mb-1">
                  <div v-if="data.current_label">
                    <Link :href="data.url.contact.index" class="text-blue-500 hover:underline">
                      {{ $t('View all') }}
                    </Link>
                  </div>
                  <div v-if="!data.current_label">
                    {{ $t('View all') }}
                  </div>
                </li>
                <li v-for="label in data.labels" :key="label.id" class="mb-1">
                  <div v-if="label.id !== data.current_label">
                    <Link :href="label.url.show" class="text-blue-500 hover:underline">
                      {{ label.name }}
                    </Link>
                    <span class="text-sm text-gray-500">({{ label.count }})</span>
                  </div>
                  <div v-if="label.id === data.current_label">
                    {{ label.name }} <span class="text-sm text-gray-500">({{ label.count }})</span>
                  </div>
                </li>
              </ul>

              <p v-else class="text-sm text-gray-500">
                {{ $t('No labels yet.') }}
              </p>
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <!-- title + cta -->
            <div class="mb-3 flex items-center justify-between">
              <h3>
                <span class="me-1"> 🥸 </span>
                {{ $t('All contacts in the vault') }}
              </h3>

              <div class="flex items-center">
                <dropdown
                  v-model="form.sort_order"
                  :data="data.contact_sort_orders"
                  :required="false"
                  :dropdown-class="'block w-full me-2'"
                  @change="update()" />

                <pretty-link
                  v-if="layoutData.vault.permission.at_least_editor"
                  :href="data.url.contact.create"
                  :text="$t('Add a contact')"
                  class="ms-3"
                  :icon="'plus'" />
              </div>
            </div>

            <!-- contact list -->
            <ul
              class="contact-list mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <li
                v-for="contact in data.contacts"
                :key="contact.id"
                class="flex items-center border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
                <avatar :data="contact.avatar" :class="'me-2 h-5 w-5 rounded-full'" />

                <Link :href="contact.url.show" class="text-blue-500 hover:underline">
                  {{ contact.name }}
                </Link>
                <span style="margin: 0px 0px 0px 70%">{{ contact.updated_at }}</span
                ><svg
                  class="ms-2 h-4 w-4"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"></path>
                </svg>
              </li>
            </ul>

            <!-- pagination -->
            <Pagination :items="paginator" />
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

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
