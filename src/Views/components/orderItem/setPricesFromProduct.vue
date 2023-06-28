<script type="text/javascript">
export default {
    props : ['row', 'model'],

    mounted(){
        //We need use this kind of watched, because
        //if we would watch whole .row object.
        //Previous old values in .row will be same as new.
        this.$watch('watchedAttributes', this.onObjectChange);
    },

    computed: {
        watchedAttributes(){
            return {
                id : this.row.id,
                product_id : this.row.product_id,
            };
        },
    },

    methods: {
        onObjectChange(row, oldRow){
            //We dows not want prices when row changes
            if ( row.id != oldRow.id ) {
                return;
            }

            //If product has been changed
            if ( row.product_id != oldRow.product_id ) {
                this.updateProductPrices(row.product_id);
            }

        },
        updateProductPrices(id){
            var product = this.model.getOptionValue('product_id', id);

            //If product does not have variants
            this.row.price = product ? product.priceWithoutVat : null;
            this.row.price_vat = product ? product.priceWithVat : null;
            this.row.vat = product ? product.vatValue : null;
        },
    }
}
</script>