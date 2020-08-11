<template>
    <div></div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows'],

    created(){
        this.removeUsedAttributes();
    },

    methods: {
        removeUsedAttributes(){
            this.model.optionsFilter('attribute_id', (key, value) => {
                var used = this.rows.map(attribute => attribute.attribute_id).indexOf(key),
                    isSelected = this.row.attribute_id == key;

                return used == -1 || isSelected;
            });
        },
    }
}
</script>