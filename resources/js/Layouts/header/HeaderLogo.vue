<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link to="/" class="lg:hidden">
        <img class="dark:hidden h-9 w-auto mx-auto" src="/images/logo/logo-light.png" alt="Logo" />
        <img
            class="hidden dark:block h-9 w-auto mx-auto"
            src="/images/logo/logo-dark.png"
            alt="Logo"
        />
    </Link>
</template>
