<template>
    <div class="form-group">
        <div class="row">
            <div class="col-md-4">
                <label>{{ field.name }} <span class="required" v-if="required">*</span></label>
                <input type="number" :name="field_key" :value="valueOrDefault" @keyup="onChange" class="form-control">
            </div>
            <div class="col-md-4">
                <label>DPH</label>
                <input type="number" :value="taxSize" disabled class="form-control">
            </div>
            <div class="col-md-4">
                <label>Cena s DPH</label>
                <input type="number" step=".2" :value="taxPrice" @keyup="changePrice" @change="recalculateWithoutTaxPrice" class="form-control">
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['field_key', 'field', 'row', 'model', 'required'],

    data(){
        return {
            tax : 0,
        }
    },

    mounted(){
        this.bindTaxChange();

        this.changeTaxValue(this.row.tax_id);
    },

    computed: {
        valueOrDefault(){
            //We want rewrite value only if is initial null state
            if ( _.isNil(this.field.value) ) {
                return this.field.default||0;
            }

            return this.field.value;
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
        changePrice : _.debounce(function(e){
            this.recalculateWithoutTaxPrice(e);
        }, 1500),
        recalculateWithoutTaxPrice(e){
            this.field.value = (e.target.value / (1 + (this.tax / 100))).toFixed(2);
        },
        bindTaxChange(){
            this.$watch('row.tax_id', this.changeTaxValue);
        },
        changeTaxValue(tax_id){
            var options = this.model.fields['tax_id'].options;

            for ( var i = 0; i < options.length; i++ ) {
                if ( options[i][0] == tax_id ) {
                    this.tax = options[i][1].tax;
                }
            }
        }
    }
}
</script>