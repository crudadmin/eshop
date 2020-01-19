<script type="text/javascript">
export default {
    props : ['row', 'model'],

    mounted(){
        this.reloadPricesOnType();
    },

    computed: {
        isPriceWithTaxAndWithoutSame(){
            return this.changeTax(this.row.price||0) == parseFloat(this.row.price_tax).toFixed(2);
        },
    },

    methods: {
        reloadPricesOnType(){
            //On price changed
            this.$watch('row.price', price => {
                if ( this.row.manual_price == false ) {
                    return;
                }

                if ( ! this.isPriceWithTaxAndWithoutSame ) {
                    this.row.price_tax = this.changeTax(price);
                }
            });

            this.$watch('row.price_tax', price => {
                if ( this.row.manual_price == false ) {
                    return;
                }

                if ( ! this.isPriceWithTaxAndWithoutSame ) {
                    this.row.price = this.changeTax(price, true);
                }
            })

            this.$watch('row.tax', tax => {
                if ( this.row.manual_price == false ) {
                    return;
                }

                this.row.price_tax = this.changeTax(this.row.price);
            })
        },
        changeTax(price, remove){
            var tax = (this.row.tax||0);

            if ( remove === true ) {
                return parseFloat(price / (1 + (tax / 100))).toFixed(2);
            }

            return parseFloat(price * (1 + (tax / 100))).toFixed(2);
        },
    }
}
</script>