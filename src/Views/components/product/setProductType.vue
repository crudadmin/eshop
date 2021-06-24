<script type="text/javascript">
export default {
    props : ['model', 'field'],

    mounted(){
        if ( this.model.getParentModels().length > 0 ) {
            this.field.default = 'variant';

            this.model.setValue('product_type', 'variant');
        }
    },
}
</script>