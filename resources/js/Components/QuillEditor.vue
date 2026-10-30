<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  maxLength: { type: Number, default: 255 },
  toolbar: { type: Array, default: () => [
    ['bold', 'italic', 'underline', 'strike'],    
    ['blockquote', 'link', 'image'],                                 
    [{ list: 'ordered' }, { list: 'bullet' }],                             
    [{ header: [1, 2, 3, 4, 5, 6, false] }],
    [{ color: [] }, { background: [] }],          
    [{ font: [] }],
    [{ align: [] }],  
  ] },
  editorHeight: { type: String, default: '200px' },
label: { type: String, default: '' }, 
});

const emit = defineEmits(['update:modelValue']);
const editorRef = ref(null);
const charCount = ref(0);
let quill;

onMounted(() => {
  nextTick().then(() => {
    quill = new Quill(editorRef.value, {
      theme: 'snow',
      placeholder: props.placeholder,
      modules: { toolbar: props.toolbar },
    });

    quill.root.innerHTML = props.modelValue || '';
    charCount.value = quill.getText().trim().length;

    quill.on('text-change', () => {
      let text = quill.getText().trim();

      if (text.length > props.maxLength) {
        const truncated = text.substring(0, props.maxLength);
        quill.deleteText(0, quill.getLength());
        quill.insertText(0, truncated);
      }

      charCount.value = quill.getText().trim().length;
      emit('update:modelValue', quill.root.innerHTML);
    });
  });
});

watch(() => props.modelValue, (val) => {
  if (quill && quill.root.innerHTML !== val) {
    quill.root.innerHTML = val || '';
    charCount.value = quill.getText().trim().length;
  }
});
</script>

<template>
  <div>

    <!-- Optional label -->
    <label v-if="label" class="mb-2 block text-sm dark:text-gray-100">
      {{ label }}
    </label>

    <div ref="editorRef" :style="{ minHeight: editorHeight }" class="bg-white dark:bg-gray-800"></div>
    <p class="text-sm text-gray-500 mt-1 text-right">
      {{ charCount }}/{{ maxLength }}
    </p>
  </div>
</template>
