<template>
    <div>
        <button
            v-for="(property, key) in model.getSettings('state_filter')"
            @click="setFilter(key)"
            data-toggle="tooltip"
            :title="property.title"
            type="button"
            class="btn mr-1"
            :class="{
                'btn-primary': filterId.includes(key),
                'btn-default': !filterId.includes(key),
            }"
        >
            {{ property.name }}
        </button>
    </div>
</template>

<script type="text/javascript">
export default {
    props: ['model'],

    data() {
        return {
            filterId: [],
        };
    },
    methods: {
        setFilter(filterId) {
            this.filterId = _.xor(this.filterId, [filterId]);

            this.model.setScope('setFilterProperty', this.filterId.join(','));

            this.model.loadRows();
        },
    },
};
</script>

<style lang="scss" scoped></style>
