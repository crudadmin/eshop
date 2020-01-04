<template>
    <div class="form-group">
        <div class="row">
            <div class="col-md-4">
                <label>{{ field.name }}</label>
                <input type="number" :name="key" :value="valueOrDefault" @keyup="onChange" class="form-control">
            </div>
            <div class="col-md-4">
                <label>DPH</label>
                <input type="number" :value="taxSize" disabled class="form-control">
            </div>
            <div class="col-md-4">
                <label>Cena s DPH</label>
                <input type="number" step=".2" :value="taxPrice" @keyup="changePrice | debounce 1500" class="form-control">
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['key', 'field', 'row', 'model'],

    data(){
        return {
            tax : 0,
        }
    },

    ready(){
        /*
         * You can watch actual field value, or other form fields in row variable
         */
        this.bindTaxChange();
    },

    computed: {
        valueOrDefault(){
            return this.field.value || this.field.default || 0;
        },
        taxSize(){
            return (this.field.value * (this.tax / 100)).toFixed(2);
        },
        taxPrice(){
            return (this.field.value * (1 + (this.tax / 100))).toFixed(2);
        },
        value(){
            return this.field.value || this.field.default || 0;
        },
    },

    methods: {
        onChange(e){
            this.field.value = e.target.value;
        },
        bindTaxChange(){

            //Taxes from order columns
            if ( ['delivery_price', 'payment_method_price'].indexOf(this.key) > -1 )
            {
                var key = this.key == 'delivery_price' ? 'delivery_tax' : 'payment_method_tax';

                this.$watch('row.'+key, function(value){
                    this.tax = value;
                });

                return;
            }

            this.$watch('row.tax_id', function(value){
                var options = this.model.fields['tax_id'].options;

                for ( var i = 0; i < options.length; i++ )
                {
                    if ( options[i][0] == value )
                        this.tax = options[i][1].tax;
                }
            });
        },
        changePrice(e){
            this.field.value = (e.target.value / (1 + (this.tax / 100))).toFixed(2);
        },
    }
}
</script>