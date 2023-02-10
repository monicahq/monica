<script setup>
import { ref, reactive } from 'vue';
import { Link, useForm } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import { trans } from 'laravel-vue-i18n';
import JetDialogModal from '@/Components/Jetstream/DialogModal.vue';
import JetConfirmationModal from '@/Components/Jetstream/ConfirmationModal.vue';
import JetButton from '@/Components/Button.vue';
import JetDangerButton from '@/Components/Jetstream/DangerButton.vue';
import JetSecondaryButton from '@/Components/Jetstream/SecondaryButton.vue';
import Layout from '@/Shared/Layout.vue';
import ContactName from '@/Shared/Modules/ContactName.vue';
import ContactAvatar from '@/Shared/Modules/ContactAvatar.vue';
import GenderPronoun from '@/Shared/Modules/GenderPronoun.vue';
import FamilySummary from '@/Shared/Modules/FamilySummary.vue';
import Notes from '@/Shared/Modules/Notes.vue';
import ImportantDates from '@/Shared/Modules/ImportantDates.vue';
import Labels from '@/Shared/Modules/Labels.vue';
import Reminders from '@/Shared/Modules/Reminders.vue';
import Feed from '@/Shared/Modules/Feed.vue';
import Loans from '@/Shared/Modules/Loans.vue';
import JobInformation from '@/Shared/Modules/JobInformation.vue';
import Relationships from '@/Shared/Modules/Relationships.vue';
import Tasks from '@/Shared/Modules/Tasks.vue';
import Calls from '@/Shared/Modules/Calls.vue';
import Pets from '@/Shared/Modules/Pets.vue';
import Goals from '@/Shared/Modules/Goals.vue';
import Addresses from '@/Shared/Modules/Addresses.vue';
import Groups from '@/Shared/Modules/Groups.vue';
import ContactInformation from '@/Shared/Modules/ContactInformation.vue';
import Documents from '@/Shared/Modules/Documents.vue';
import Photos from '@/Shared/Modules/Photos.vue';
import Religion from '@/Shared/Modules/Religion.vue';
import Posts from '@/Shared/Modules/Posts.vue';
import LifeEvent from '@/Shared/Modules/LifeEvent.vue';
import Uploadcare from '@/Components/Uploadcare.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const form = useForm({
  searchTerm: null,
  uuid: null,
  name: null,
  original_url: null,
  cdn_url: null,
  mime_type: null,
  size: null,
  errors: [],
});

const deletingContact = ref(false);
const deleteContactForm = reactive({
  processing: false,
});

const togglingArchive = ref(false);
const toggleArchiveForm = reactive({
  processing: false,
});

const destroy = () => {
  deleteContactForm.processing = true;

  axios
    .delete(props.data.url.destroy)
    .then((response) => {
      deleteContactForm.processing = false;

      localStorage.success = trans('contact.contact_delete_success');
      Inertia.visit(response.data.data);
    })
    .catch((error) => {
      deleteContactForm.processing = false;
      form.errors = error.response.data;
    });
};

const toggleArchive = () => {
  toggleArchiveForm.processing = true;

  axios
    .put(props.data.url.toggle_archive)
    .then((response) => {
      toggleArchiveForm.processing = false;

      localStorage.success = trans('app.notification_flash_changes_saved');
      Inertia.visit(response.data.data);
    })
    .catch((error) => {
      toggleArchiveForm.processing = false;
      form.errors = error.response.data;
    });
};

const onSuccess = (file) => {
  form.uuid = file.uuid;
  form.name = file.name;
  form.original_url = file.originalUrl;
  form.cdn_url = file.cdnUrl;
  form.mime_type = file.mimeType;
  form.size = file.size;

  upload();
};

const upload = () => {
  axios
    .put(props.data.url.update_avatar, form)
    .then((response) => {
      Inertia.visit(response.data.data);
      flash(trans('contact.photos_new_success'), 'success');
    })
    .catch((error) => {
      form.errors = error.response.data;
    });
};

const destroyAvatar = () => {
  axios
    .delete(props.data.url.destroy_avatar)
    .then((response) => {
      Inertia.visit(response.data.data);
      localStorage.success = trans('app.notification_flash_changes_saved');
    })
    .catch((error) => {
      form.errors = error.response.data;
    });
};
</script>

<style lang="scss" scoped>
.special-grid {
  grid-template-columns: 300px 1fr;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}

.group-list-item:not(:last-child):after {
  content: ',';
}
</style>

<template>
  <Layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <Link :href="layoutData.vault.url.contacts" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_contact_index') }}
              </Link>
            </li>
            <li class="relative mr-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">
              {{ $t('app.breadcrumb_contact_show', { name: data.contact_name.name }) }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- banner if contact is archived -->
        <!-- this is based on the `listed` boolean on the contact object -->
        <div v-if="!data.listed" class="mb-8 rounded-lg border border-gray-300 px-3 py-2 text-center">
          <span class="mr-4"> 🕸️ </span>
          {{ $t('contact.contact_archived') }}
          <span class="ml-4"> 🕷️ </span>
        </div>

        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-3">
            <div v-if="data.contact_information.length > 0" class="mb-8">
              <div v-for="module in data.contact_information" :key="module.id">
                <ContactAvatar v-if="module.type == 'avatar'" :data="module.data" />

                <ContactName v-else-if="module.type == 'contact_names'" :data="module.data" />

                <FamilySummary v-else-if="module.type == 'family_summary'" :data="module.data" />

                <GenderPronoun v-else-if="module.type == 'gender_pronoun'" :data="module.data" />

                <ImportantDates v-else-if="module.type == 'important_dates'" :data="module.data" />

                <Labels v-else-if="module.type == 'labels'" :data="module.data" />

                <JobInformation v-else-if="module.type == 'company'" :data="module.data" />

                <Religion v-else-if="module.type == 'religions'" :data="module.data" />
              </div>
            </div>

            <ul class="text-xs">
              <!-- remove avatar -->
              <li v-if="data.avatar.hasFile" class="mb-2">
                <span @click.prevent="destroyAvatar()" class="cursor-pointer text-blue-500 hover:underline">
                  Remove avatar
                </span>
              </li>
              <!-- upload new avatar -->
              <li v-if="!data.avatar.hasFile" class="mb-2">
                <Uploadcare
                  v-if="data.avatar.uploadcarePublicKey && data.avatar.canUploadFile"
                  :public-key="data.avatar.uploadcarePublicKey"
                  :tabs="'file'"
                  :multiple="false"
                  :preview-step="false"
                  @success="onSuccess"
                  @error="onError">
                  <span class="cursor-pointer text-blue-500 hover:underline"> Upload photo as avatar </span>
                </Uploadcare>
              </li>
              <!-- archive contact -->
              <li v-if="data.listed && data.options.can_be_archived" class="mb-2">
                <span class="cursor-pointer text-blue-500 hover:underline" @click="togglingArchive = true">
                  {{ $t('contact.contact_archive_cta') }}
                </span>
              </li>
              <!-- unarchive contact -->
              <li v-if="!data.listed" class="mb-2">
                <span class="cursor-pointer text-blue-500 hover:underline" @click="togglingArchive = true">
                  {{ $t('contact.contact_unarchive_cta') }}
                </span>
              </li>
              <!-- change template -->
              <li class="mb-2">
                <Link :href="data.url.update_template" class="cursor-pointer text-blue-500 hover:underline">
                  {{ $t('contact.contact_change_template_cta') }}
                </Link>
              </li>
              <!-- move contact to another vault -->
              <li class="mb-2">
                <Link :href="data.url.move_contact" class="cursor-pointer text-blue-500 hover:underline">
                  {{ $t('contact.contact_move_contact_cta') }}
                </Link>
              </li>
              <!-- delete contact -->
              <li v-if="data.options.can_be_deleted">
                <span class="cursor-pointer text-blue-500 hover:underline" @click="deletingContact = true">
                  {{ $t('contact.contact_delete_cta') }}
                </span>
              </li>
            </ul>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <!-- family summary -->
            <div v-if="data.group_summary_information.length > 0">
              <div class="mb-6 flex rounded border border-gray-200 p-3 dark:border-gray-700">
                <img src="/img/group.svg" class="mr-2 h-6 w-6" />
                <ul>
                  <li class="mr-2 inline">Part of</li>
                  <li
                    v-for="group in data.group_summary_information"
                    :key="group.id"
                    class="group-list-item mr-2 inline">
                    <Link :href="group.url.show" class="text-blue-500 hover:underline">
                      {{ group.name }}
                    </Link>
                  </li>
                </ul>
              </div>
            </div>

            <!-- all the pages -->
            <div class="mb-8 w-full border-b border-gray-200 dark:border-gray-700">
              <div class="flex overflow-x-auto">
                <div v-for="page in data.template_pages" :key="page.id" class="mr-2 flex-none">
                  <Link
                    :href="page.url.show"
                    :class="{ 'border-orange-500 hover:border-orange-500': page.selected }"
                    class="inline-block border-b-2 border-transparent px-2 pb-2 hover:border-gray-200 hover:dark:border-gray-700">
                    <span class="mb-0 block rounded-sm px-3 py-1 hover:bg-gray-100 hover:dark:bg-gray-900">{{
                      page.name
                    }}</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- all the modules -->
            <div v-if="data.modules.length > 0">
              <div v-for="module in data.modules" :key="module.id">
                <Notes v-if="module.type == 'notes'" :data="module.data" />

                <Reminders v-else-if="module.type == 'reminders'" :data="module.data" />

                <Feed v-else-if="module.type == 'feed'" :url="module.data" />

                <Loans v-else-if="module.type == 'loans'" :data="module.data" :layout-data="layoutData" />

                <Relationships v-else-if="module.type == 'relationships'" :data="module.data" />

                <Tasks v-else-if="module.type == 'tasks'" :data="module.data" />

                <Calls v-else-if="module.type == 'calls'" :data="module.data" />

                <Pets v-else-if="module.type == 'pets'" :data="module.data" />

                <Goals v-else-if="module.type == 'goals'" :data="module.data" />

                <Addresses v-else-if="module.type == 'addresses'" :data="module.data" />

                <Groups v-else-if="module.type == 'groups'" :data="module.data" />

                <ContactInformation v-else-if="module.type == 'contact_information'" :data="module.data" />

                <Documents v-else-if="module.type == 'documents'" :data="module.data" />

                <Photos v-else-if="module.type == 'photos'" :data="module.data" />

                <Posts v-else-if="module.type == 'posts'" :data="module.data" />

                <Life-Event v-else-if="module.type == 'life_events'" :data="module.data" :layout-data="layoutData" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Contact Confirmation Modal -->
      <JetConfirmationModal :show="deletingContact" @close="deletingContact = false">
        <template #title>
          {{ $t('Delete contact') }}
        </template>

        <template #content>
          {{
            $t(
              'Are you sure you would like to delete this contact? This will remove everything we know about this contact.',
            )
          }}
        </template>

        <template #footer>
          <JetSecondaryButton @click="deletingContact = false">
            {{ $t('Cancel') }}
          </JetSecondaryButton>

          <JetDangerButton
            class="ml-3"
            :class="{ 'opacity-25': deleteContactForm.processing }"
            :disabled="deleteContactForm.processing"
            @click="destroy">
            {{ $t('Delete') }}
          </JetDangerButton>
        </template>
      </JetConfirmationModal>

      <!-- Archive Contact Confirmation Modal -->
      <JetDialogModal :show="togglingArchive" @close="togglingArchive = false">
        <template #title>
          {{ $t('Archive contact') }}
        </template>

        <template #content>
          {{ $t('Are you sure you would like to archive this contact?') }}
        </template>

        <template #footer>
          <JetSecondaryButton @click="togglingArchive = false">
            {{ $t('Cancel') }}
          </JetSecondaryButton>

          <JetButton
            class="ml-3"
            :class="{ 'opacity-25': toggleArchiveForm.processing }"
            :disabled="toggleArchiveForm.processing"
            @click="toggleArchive">
            {{ $t('Archive') }}
          </JetButton>
        </template>
      </JetDialogModal>
    </main>
  </Layout>
</template>
