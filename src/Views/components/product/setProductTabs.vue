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
        this.$watch('row.type', function(type){
            this.setTabsVisibility(type);
        });

        this.setTabsVisibility(this.model.fields.type.value);
    },

    methods: {
        setTabsVisibility(type){
            type = type||this.model.fields.type.default;

            var hasVariants = _.find(this.model.fields.type.options, { 0 : type })[1].variants;

            this.model.setTabVisibility('products_variants', hasVariants);
            // this.model.setTabVisibility('price', !hasVariants);
            // this.model.hideFromForm('warehouse_quantity', hasVariants);
        }
    }
}
</script>