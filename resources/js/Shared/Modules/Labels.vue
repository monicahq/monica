<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}

.icon-note {
  top: -1px;
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

<template>
  <div class="mb-4">
    <div class="mb-3 items-center justify-between border-b border-gray-200 sm:flex">
      <div class="mb-2 text-xs sm:mb-0">Labels</div>
      <span v-if="!editLabelModalShown" @click="showEditModal" class="relative cursor-pointer">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-sidebar relative inline h-3 w-3 text-gray-300 hover:text-gray-600"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
      </span>

      <!-- close button -->
      <span
        v-if="editLabelModalShown"
        @click="editLabelModalShown = false"
        class="cursor-pointer text-xs text-gray-300">
        Close
      </span>
    </div>

    <!-- edit labels -->
    <div v-if="editLabelModalShown" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <!-- filter list of labels -->
      <div class="border-b border-gray-200 p-2">
        <errors :errors="form.errors" />

        <text-input
          :ref="'newPage'"
          v-model="form.search"
          :type="'text'"
          :placeholder="'Filter list'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          @esc-key-pressed="editLabelModalShown = false" />
      </div>

      <!-- labels in vault -->
      <ul class="label-list overflow-auto bg-white" :class="filteredLabels.length > 0 ? 'h-40' : ''">
        <li
          v-for="label in filteredLabels"
          :key="label.id"
          @click="set(label)"
          class="flex cursor-pointer items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50">
          <div>
            <span class="mr-2 inline-block h-4 w-4 rounded-full" :class="label.bg_color"></span>
            <span>{{ label.name }}</span>
          </div>

          <svg
            v-if="label.taken"
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 text-green-700"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </li>

        <!-- case if the label does not exist and needs to be created -->
        <li
          v-if="filteredLabels.length == 0 && form.search.length != ''"
          class="cursor-pointer border-b border-gray-200 px-3 py-2 hover:bg-slate-50"
          @click="store()">
          Create new label <span class="italic">"{{ form.search }}"</span>
        </li>

        <!-- blank state when there is no label at all -->
        <li
          v-if="filteredLabels.length == 0 && form.search.length == ''"
          class="border-b border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-slate-50">
          Please type a few characters to create a new label.
        </li>
      </ul>
    </div>

    <!-- list of labels -->
    <div class="flex flex-wrap">
      <span
        v-for="label in localLabels"
        :key="label.id"
        class="mr-2 mb-2 inline-block rounded py-1 px-2 text-xs font-semibold last:mr-0"
        :class="label.bg_color + ' ' + label.text_color">
        {{ label.name }}
      </span>
    </div>

    <!-- blank state -->
    <p v-if="localLabels.length == 0" class="text-sm text-gray-600">Not set</p>
  </div>
</template>

<script>
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
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

  created() {
    this.localLabels = this.data.labels_in_contact;
    this.localLabelsInVault = this.data.labels_in_vault;
  },

  computed: {
    filteredLabels() {
      return this.localLabelsInVault.filter((label) => {
        return label.name.toLowerCase().indexOf(this.form.search.toLowerCase()) > -1;
      });
    },
  },

  methods: {
    showEditModal() {
      this.form.name = '';
      this.editLabelModalShown = true;

      this.$nextTick(() => {
        this.$refs.label.focus();
      });
    },

    store() {
      this.form.name = this.form.search;

      axios
        .post(this.data.url.store, this.form)
        .then((response) => {
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