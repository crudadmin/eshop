<template>
    <div>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows'],

    mounted(){
        /*
         * You can watch actual field value, or other form fields in row variable
         */
        this.$watch('row.product_type', function(type){
            this.setTabsVisibility(type);
        });

        this.setTabsVisibility(this.model.fields.product_type.value);
    },

    methods: {
        setTabsVisibility(type){
            var selectedType = _.find(this.model.fields.product_type.options, { 0 : type });

            var hasVariants = selectedType ? selectedType[1].variants : false,
                hasOrderableVariants = selectedType ? selectedType[1].orderableVariants : false;

            this.model.setTabVisibility('products_variants', hasVariants);
            this.model.setTabVisibility('price', !hasOrderableVariants);
            this.model.hideFromForm('warehouse_quantity', hasOrderableVariants);
            this.model.hideFromForm('ean', hasOrderableVariants);
            this.model.hideFromForm('code', hasOrderableVariants);
        }
    }
}
</script>