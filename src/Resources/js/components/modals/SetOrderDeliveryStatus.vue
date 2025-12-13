<template>
    <div @keyup.enter.prevent.stop>
        <div class="form-group">
            <label>{{ __('Aký stav si prajete zmeniť?') }}</label>
            <Multiselect
                name="status"
                :options="statuses"
                :placeholder="__('Vyberte stav')"
                :searchable="true"
                :nativeSupport="true"
                :appendToBody="true"
            />
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    data: {
        required: true,
    },
});

const statuses = computed(() => props.data.statuses);
</script>
