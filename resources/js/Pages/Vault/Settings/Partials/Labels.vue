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

<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0"><span class="mr-1"> 🏷 </span> All the labels used in the vault</h3>
      <pretty-button v-if="!createlabelModalShown" :text="'Add a label'" :icon="'plus'" @click="showLabelModal" />
    </div>

    <!-- modal to create a new label -->
    <form
      v-if="createlabelModalShown"
      class="bg-form mb-6 rounded-lg border border-gray-200"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5">
        <errors :errors="form.errors" />

        <text-input
          :ref="'newLabel'"
          v-model="form.name"
          :label="'Name'"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          :div-outer-class="'mb-4'"
          @esc-key-pressed="createlabelModalShown = false" />

        <p class="mb-2 block text-sm">Choose a color</p>
        <div class="grid grid-cols-8 gap-4">
          <div v-for="color in data.label_colors" :key="color.bg_color" class="flex items-center">
            <input
              :id="color.bg_color"
              v-model="form.bg_color"
              :value="color.bg_color"
              name="name-order"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500"
              @click="form.text_color = color.text_color" />
            <label :for="color.bg_color" class="ml-2 inline-block cursor-pointer text-sm font-medium text-gray-700">
              <div class="rounded p-4" :class="color.bg_color"></div>
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="'Cancel'" :classes="'mr-3'" @click="createlabelModalShown = false" />
        <pretty-button :text="'Create label'" :state="loadingState" :icon="'plus'" :classes="'save'" />
      </div>
    </form>

    <!-- list of label -->
    <ul v-if="localLabels.length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <li v-for="label in localLabels" :key="label.id" class="item-list border-b border-gray-200 hover:bg-slate-50">
        <!-- detail of the label -->
        <div v-if="editLabelModalShownId != label.id" class="flex items-center justify-between px-5 py-2">
          <span class="flex items-center text-base">
            <div class="mr-2 inline-block h-4 w-4 rounded-full" :class="label.bg_color"></div>
            <span class="mr-2">{{ label.name }}</span>
            <span v-if="label.count > 0" class="text-xs text-gray-500">({{ label.count }} contacts)</span>
          </span>

          <!-- actions -->
          <ul class="text-sm">
            <li class="mr-4 inline cursor-pointer text-sky-500 hover:text-blue-900" @click="updateLabelModal(label)">
              Edit
            </li>
            <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(label)">Delete</li>
          </ul>
        </div>

        <!-- edit a label modal -->
        <form
          v-if="editLabelModalShownId == label.id"
          class="item-list bg-form border-b border-gray-200 hover:bg-slate-50"
          @submit.prevent="update(label)">
          <div class="border-b border-gray-200 p-5">
            <errors :errors="form.errors" />

            <text-input
              :ref="'rename' + label.id"
              v-model="form.name"
              :label="'Name'"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              :div-outer-class="'mb-4'"
              @esc-key-pressed="editLabelModalShownId = 0" />

            <p class="mb-2 block text-sm">Choose a color</p>
            <div class="grid grid-cols-8 gap-4">
              <div v-for="color in data.label_colors" :key="color.bg_color" class="flex items-center">
                <input
                  :id="color.bg_color"
                  v-model="form.bg_color"
                  :value="color.bg_color"
                  name="name-order"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500"
                  @click="form.text_color = color.text_color" />
                <label :for="color.bg_color" class="ml-2 inline-block cursor-pointer text-sm font-medium text-gray-700">
                  <div class="rounded p-4" :class="color.bg_color"></div>
                </label>
              </div>
            </div>
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="'Cancel'" :classes="'mr-3'" @click.prevent="editLabelModalShownId = 0" />
            <pretty-button :text="'Rename'" :state="loadingState" :icon="'check'" :classes="'save'" />
          </div>
        </form>
      </li>
    </ul>

    <!-- blank state -->
    <div v-if="localLabels.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
      <p class="p-5 text-center">Labels let you classify contacts using a system that matters to you.</p>
    </div>
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
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      createlabelModalShown: false,
      editLabelModalShownId: 0,
      localLabels: [],
      form: {
        name: '',
        description: '',
        bg_color: '',
        text_color: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localLabels = this.data.labels;
    this.form.bg_color = this.data.label_colors[0].bg_color;
    this.form.text_color = this.data.label_colors[0].text_color;
  },

  methods: {
    showLabelModal() {
      this.form.name = '';
      this.createlabelModalShown = true;

      this.$nextTick(() => {
        this.$refs.newLabel.focus();
      });
    },

    updateLabelModal(label) {
      this.form.name = label.name;
      this.editLabelModalShownId = label.id;
      this.form.bg_color = label.bg_color;
      this.form.text_color = label.text_color;
    },

    submit() {
      this.loadingState = 'loading';

      axios
        .post(this.data.url.label_store, this.form)
        .then((response) => {
          this.flash('The label has been created', 'success');
          this.localLabels.unshift(response.data.data);
          this.loadingState = null;
          this.createlabelModalShown = false;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    update(label) {
      this.loadingState = 'loading';

      axios
        .put(label.url.update, this.form)
        .then((response) => {
          this.flash('The label has been updated', 'success');
          this.localLabels[this.localLabels.findIndex((x) => x.id === label.id)] = response.data.data;
          this.loadingState = null;
          this.editLabelModalShownId = 0;
        })
        .catch((error) => {
          this.loadingState = null;
          this.form.errors = error.response.data;
        });
    },

    destroy(label) {
      if (
        confirm(
          "Are you sure? This will remove the labels from all contacts, but won't delete the contacts themselves.",
        )
      ) {
        axios
          .delete(label.url.destroy)
          .then((response) => {
            this.flash('The label has been deleted', 'success');
            var id = this.localLabels.findIndex((x) => x.id === label.id);
            this.localLabels.splice(id, 1);
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>
