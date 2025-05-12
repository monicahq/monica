<template>
  <div class="mb-4">
    <div class="pb-1 mb-2 items-center justify-between border-b border-gray-200 dark:border-gray-700 flex">
      <div class="text-xs">{{ $t('Labels') }}</div>
      <span v-if="!editLabelModalShown" class="relative cursor-pointer" @click="showEditModal">
        <Pencil class="h-3 w-3 text-gray-400" />
      </span>

      <!-- close button -->
      <span
        v-if="editLabelModalShown"
        class="cursor-pointer text-xs text-gray-600 dark:text-gray-400"
        @click="editLabelModalShown = false">
        {{ $t('Close') }}
      </span>
    </div>

    <!-- edit labels -->
    <div
      v-if="editLabelModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <!-- filter list of labels -->
      <div class="border-b border-gray-200 p-2 dark:border-gray-700">
        <errors :errors="form.errors" />

        <text-input
          ref="label"
          v-model="form.search"
          :type="'text'"
          :placeholder="$t('Filter list or create a new label')"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          @esc-key-pressed="editLabelModalShown = false" />
      </div>

      <!-- labels in vault -->
      <ul class="label-list overflow-auto bg-white dark:bg-gray-900" :class="filteredLabels.length > 0 ? 'h-40' : ''">
        <!-- case if the label does not exist and needs to be created -->
        <li
          v-if="showCreateNewLabel"
          class="cursor-pointer border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800"
          @click="store()">
          {{ $t('Create new label') }} <span class="italic">"{{ form.search }}"</span>
        </li>

        <li
          v-for="label in filteredLabels"
          :key="label.id"
          class="flex cursor-pointer items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
          @click="set(label)">
          <div>
            <span class="me-2 inline-block h-4 w-4 rounded-full" :class="label.bg_color" />
            <span>{{ label.name }}</span>
          </div>

          <CheckedIcon v-if="label.taken" />
        </li>

        <!-- blank state when there is no label at all -->
        <li
          v-if="filteredLabels.length === 0 && form.search.length === ''"
          class="border-b border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-400 dark:hover:bg-slate-800">
          {{ $t('Please type a few characters to create a new label.') }}
        </li>
      </ul>
    </div>

    <!-- list of labels -->
    <div class="flex flex-wrap">
      <span
        v-for="label in localLabels"
        :key="label.id"
        class="mb-2 me-2 inline-block rounded-xs px-2 py-1 text-xs font-semibold last:me-0"
        :class="label.bg_color + ' ' + label.text_color">
        <InertiaLink :href="label.url.show">{{ label.name }}</InertiaLink>
      </span>
    </div>

    <!-- blank state -->
    <p v-if="localLabels.length === 0" class="text-sm text-gray-600 dark:text-gray-400">{{ $t('Not set') }}</p>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import CheckedIcon from '@/Shared/Icons/CheckedIcon.vue';
import { Pencil } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    TextInput,
    Errors,
    Pencil,
    CheckedIcon,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      editLabelModalShown: false,
      localLabels: [],
      localLabelsInVault: [],
      form: {
        search: '',
        name: '',
        errors: [],
      },
    };
  },

  computed: {
    showCreateNewLabel() {
      let searchTermAlreadyAdded = false;
      if (this.filteredLabels.length) {
        this.filteredLabels.forEach((label) => {
          if (label.name.toLowerCase() === this.form.search.toLowerCase()) searchTermAlreadyAdded = true;
        });
      }

      return (!searchTermAlreadyAdded || this.filteredLabels.length === 0) && this.form.search !== '';
    },
    filteredLabels() {
      return this.localLabelsInVault.filter((label) => {
        return label.name.toLowerCase().indexOf(this.form.search.toLowerCase()) > -1;
      });
    },
  },

  created() {
    this.localLabels = this.data.labels_in_contact;

    // TODO: this should not be loaded up front. we should do a async call once
    // the edit mode is active to load all the labels from the backend instead.
    this.localLabelsInVault = this.data.labels_in_vault;
  },

  methods: {
    showEditModal() {
      this.form.name = '';
      this.editLabelModalShown = true;

      this.$nextTick().then(() => {
        this.$refs.label.focus();
      });
    },

    store() {
      this.form.name = this.form.search;

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
          this.flash(this.$t('The label has been added'), 'success');
          this.form.search = '';
          this.localLabelsInVault.push(response.data.data);
          this.localLabels.push(response.data.data);
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    set(label) {
      if (label.taken) {
        this.remove(label);
        return;
      }

      axios
        .put(label.url.update)
        .then((response) => {
          this.localLabelsInVault[this.localLabelsInVault.findIndex((x) => x.id === label.id)] = response.data.data;
          this.localLabels.push(response.data.data);
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    remove(label) {
      axios
        .delete(label.url.destroy)
        .then((response) => {
          this.localLabelsInVault[this.localLabelsInVault.findIndex((x) => x.id === label.id)] = response.data.data;

          var id = this.localLabels.findIndex((x) => x.id === label.id);
          this.localLabels.splice(id, 1);
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

.label-list {
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;

  li:last-child {
    border-bottom: 0;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }

  li:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
