<template>
    <Head title="Confirm Password" />

    <div class="mb-4 text-sm text-gray-600">
        This is a secure area of the application. Please confirm your password before continuing.
    </div>

    <BreezeValidationErrors class="mb-4" />

    <form @submit.prevent="submit">
        <div>
            <BreezeLabel for="password" value="Password" />
            <BreezeInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required autocomplete="current-password" autofocus />
        </div>

        <div class="flex justify-end mt-4">
            <BreezeButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Confirm
            </BreezeButton>
        </div>
    </form>
</template>

<script>
import BreezeButton from '@/Components/Button.vue'
import BreezeGuestLayout from '@/Layouts/Guest.vue'
import BreezeInput from '@/Components/Input.vue'
import BreezeLabel from '@/Components/Label.vue'
import BreezeValidationErrors from '@/Components/ValidationErrors.vue'
import { Head } from '@inertiajs/inertia-vue3';

export default {
    layout: BreezeGuestLayout,

    components: {
        BreezeButton,
        BreezeInput,
        BreezeLabel,
        BreezeValidationErrors,
        Head,
    },

    data() {
        return {
            form: this.$inertia.form({
                password: '',
            })
        }
    },

    methods: {
        submit() {
            this.form.post(this.route('password.confirm'), {
                onFinish: () => this.form.reset(),
            })
        }
    }
}
</script>
