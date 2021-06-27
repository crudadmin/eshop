<template>
<input type="hidden" name="">
</template>

<script type="text/javascript">
export default {
    props : ['model', 'field'],

    mounted(){
        let parentModels = this.model.getParentModels();

        if ( parentModels.length > 0 ) {
            this.model.setValue('product_type', 'variant');
            this.field.default = 'variant';
        }

        //Disable variant in main product
        if ( parentModels.length == 0 ) {
            this.model.optionsFilter('product_type', (key, value) => {
                return key != 'variant';
            });
        }
    },
}
</script>