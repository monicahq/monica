<script setup>
import { Link } from '@inertiajs/vue3';
import { Popover as APopover } from 'ant-design-vue';
import CalendarIcon from '@/Shared/Icons/CalendarIcon.vue';
import PeopleIcon from '@/Shared/Icons/PeopleIcon.vue';

defineProps({
  contact: Object,
  avatarClasses: String,
  top: {
    type: String,
    default: '0px',
  },
  displayName: {
    type: Boolean,
    default: true,
  },
});
</script>

<template>
  <div class="relative inline" :style="'top: ' + top">
    <a-popover placement="bottomLeft">
      <!-- popup that appears on mouse over -->
      <template #content>
        <div class="flex">
          <!-- avatar -->
          <div class="me-2">
            <div v-if="contact.avatar.type === 'svg'" class="h-16 w-16 rounded-full" v-html="contact.avatar.content" />
            <img v-else class="h-16 w-16 rounded-full" :src="contact.avatar.content" alt="avatar" />
          </div>

          <div>
            <p class="mb-2 text-lg font-semibold">{{ contact.name }}</p>

            <!-- birthdate -->
            <p class="flex items-center">
              <CalendarIcon />

              <span v-if="contact.age">{{ contact.age }}</span>
              <span v-else class="text-sm italic text-gray-600">{{ $t('Unknown') }}</span>
            </p>

            <!-- groups -->
            <div v-if="contact.groups.length > 0" class="mt-2 flex items-start">
              <PeopleIcon />

              <ul>
                <li v-for="group in contact.groups" :key="group.id" class="group-list-item">
                  <Link class="text-blue-500 hover:underline">
                    {{ group.name }}
                  </Link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </template>
      <template v-if="displayName" #title>
        <span>{{ contact.name }}</span>
      </template>

      <!-- default state -->
      <div class="inline-flex items-center">
        <!-- avatar -->
        <div class="img relative">
          <Link :href="contact.url">
            <div v-if="contact.avatar.type === 'svg'" :class="avatarClasses" v-html="contact.avatar.content" />
            <img v-else :class="avatarClasses" :src="contact.avatar.content" alt="avatar" />
          </Link>
        </div>

        <!-- name -->
        <Link v-if="displayName" class="text-blue-500 hover:underline" :href="contact.url">{{ contact.name }}</Link>
      </div>
    </a-popover>
  </div>
</template>
