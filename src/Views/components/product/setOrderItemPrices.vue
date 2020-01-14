<script type="text/javascript">
export default {
    props : ['row', 'model'],

    mounted(){
        this.bindChanges();
    },

    methods: {
        getTaxValue(taxId){
            var tax = _.find(this.model.storeTaxes, { id : parseInt(taxId) });

            return tax.tax||0;
        },
        bindChanges(){
            //On product change bind fields
            this.$watch('row.product_id', id => {
                var product = this.model.getOptionValue('product_id', id),
                    availableVariants = this.model.fields.variant_id.options.filter(item => {
                        return item[1].product_id == id;
                    });

                //If product does not have variants
                this.row.price = product && availableVariants.length == 0 ? product.priceWithoutTax : null;
                this.row.price_tax = product && availableVariants.length == 0 ? product.priceWithTax : null;
                this.row.tax = product && availableVariants.length == 0 ? this.getTaxValue(product.tax_id) : null;
            });

            //On variant change bind fields
            this.$watch('row.variant_id', id => {
                var product = this.model.getOptionValue('variant_id', id);

                //If product does not have variants
                this.row.price = product ? product.priceWithoutTax : null;
                this.row.price_tax = product ? product.priceWithTax : null;
                this.row.tax = product ? this.getTaxValue(product.tax_id) : null;
            });
        },
    }
}
</script>