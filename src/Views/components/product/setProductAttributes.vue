<template>
    <div></div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows'],

    mounted(){
        this.removeUsedAttributes();
    },

    methods: {
        removeUsedAttributes(){
            this.model.optionsFilter('item_id', (key, value) => {
                return this.rows.map(item => item.item_id).indexOf(key) == -1;
            });
        },
    }
}
</script>