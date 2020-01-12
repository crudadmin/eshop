<template>
    <div>
        <input v-if="product_row" type="hidden" :name="field_key" :value="product_row.product_type">
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['field_key', 'field', 'row', 'model'],

    data(){
        return {
            product_row : null,
        }
    },

    mounted(){
        this.$on('getParentRow', this.updateProductRow)

        this.$emit('sendParentRow');
    },

    watch: {
        product_row: {
            deep : true,
            handler(item){
                //Copy product price into attribute in variant row. For correct * in required state
                if ( item ) {
                    this.field.value = item.product_type;
                }
            },
        }
    },

    methods: {
        updateProductRow(data){
            if ( data.table != 'products' ) {
                return;
            }

            this.product_row = data.row;
        },
    }
}
</script>