<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    }
});

</script>

<template>
    <Head title="Welcome" />
    <div class="bg-[#ffffff] min-h-screen flex items-center justify-center">
        <div class="text-center bg-[#c1daeb] w-1/4  rounded-xl shadow-lg p-10">
            <h1 class="text-4xl font-bold text-blue-900 mb-8">Wallet App</h1>
            <div class="space-y-4">
                <Link v-if="canLogin" :href="route('login')"
                    class="block bg-white text-blue-900 font-semibold px-6 py-3 rounded-lg hover:bg-blue-50 transition-colors">
                Login
                </Link>

                <Link v-if="canRegister" :href="route('register')"
                    class="block bg-blue-800 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                Cadastrar-se
                </Link>
            </div>
        </div>
    </div>
</template>
