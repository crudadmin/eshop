<template>
    <div>
        <div class="form-group">
            <label>{{ __('Vyberte typ dokladu') }}</label>

            <Multiselect
                name="invoice_type"
                :options="types"
                :placeholder="__('Vyberte typ dokladu')"
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

const types = computed(() => {
    let types = [];

    for (const type in props.data.invoice_types) {
        types.push({
            value: type,
            label: props.data.invoice_types[type].name,
        });
    }

    return types;
});
</script>
