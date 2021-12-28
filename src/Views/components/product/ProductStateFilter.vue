<template>
    <div>
        <button
            v-for="(property, key) in model.getSettings('state_filter')"
            @click="setFilter(key)"
            data-toggle="tooltip"
            :title="property.title"
            type="button"
            class="btn mr-2"
            :class="{
                'btn-primary': filterId == key,
                'btn-default': filterId != key,
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
            filterId: null,
        };
    },
    methods: {
        setFilter(filterId) {
            if (this.filterId == filterId) {
                this.filterId = null;

                this.model.removeScope('setFilterProperty');
            } else {
                this.filterId = filterId;

                this.model.setScope('setFilterProperty', filterId);
            }

            this.model.loadRows();
        },
    },
};
</script>

<style lang="scss" scoped></style>
