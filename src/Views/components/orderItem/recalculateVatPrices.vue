<script type="text/javascript">
export default {
    props : ['row', 'model'],

    mounted(){
        this.reloadPricesOnType();
    },

    computed: {
        isPriceWithVatAndWithoutSame(){
            return this.changeVat(this.row.price||0) == parseFloat(this.row.price_vat).toFixed(2);
        },
    },

    methods: {
        reloadPricesOnType(){
            //On price changed
            this.$watch('row.price', price => {
                if ( this.row.manual_price == false ) {
                    return;
                }

                if ( ! this.isPriceWithVatAndWithoutSame ) {
                    this.row.price_vat = this.changeVat(price);
                }
            });

            this.$watch('row.price_vat', price => {
                if ( this.row.manual_price == false ) {
                    return;
                }

                if ( ! this.isPriceWithVatAndWithoutSame ) {
                    this.row.price = this.changeVat(price, true);
                }
            })

            this.$watch('row.vat', vat => {
                if ( this.row.manual_price == false ) {
                    return;
                }

                this.row.price_vat = this.changeVat(this.row.price);
            })
        },
        changeVat(price, remove){
            var vat = (this.row.vat||0);

            if ( remove === true ) {
                return parseFloat(price / (1 + (vat / 100))).toFixed(2);
            }

            return parseFloat(price * (1 + (vat / 100))).toFixed(2);
        },
    }
}
</script>