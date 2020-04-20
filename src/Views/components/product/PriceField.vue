<template>
    <div class="form-group" :class="{ disabled : disabled || readonly }">
        <div class="row">
            <div class="col-md-4">
                <label>{{ field.name }} <span class="required" v-if="required">*</span></label>
                <input type="number" step=".01" :name="field_key" :value="valueOrDefault" @keyup="onChange" class="form-control" :readonly="disabled || readonly">
            </div>
            <div class="col-md-4">
                <label>DPH</label>
                <input type="number" step=".01" :value="taxSize" disabled class="form-control">
            </div>
            <div class="col-md-4">
                <label>Cena s DPH</label>
                <input type="number" step=".01" :value="taxPrice" @keyup="changePrice" @change="recalculateWithoutTaxPrice" class="form-control" :readonly="disabled || readonly">
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['field_key', 'field', 'row', 'model', 'required', 'disabled', 'readonly'],

    data(){
        return {
            tax : 0,
        }
    },

    mounted(){
        //On tax value change
        this.onTaxChange();

        //Bind default tax value
        this.changeTaxValue(this.row[this.getTaxFieldKey()]);
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
        getFieldPrefix(){
            var fieldPrefix = this.field_key.split('_').slice(0, -1).join('_');

            return fieldPrefix ? fieldPrefix+'_' : '';
        },
        hasStaticFieldTax(){
            return this.model.fields[this.getFieldPrefix()+'tax'] ? true : false;
        },
        getTaxFieldKey(){
            var field = this.getFieldPrefix()+(this.hasStaticFieldTax() ? 'tax' : 'tax_id');

            return this.model.fields[field] ? field : 'tax_id';
        },
        onChange(e){
            this.field.value = e.target.value;
        },
        changePrice : _.debounce(function(e){
            this.recalculateWithoutTaxPrice(e);
        }, 1500),
        recalculateWithoutTaxPrice(e){
            this.field.value = (e.target.value / (1 + (this.tax / 100))).toFixed(2);
        },
        onTaxChange(){
            this.$watch('row.'+this.getTaxFieldKey(), this.changeTaxValue);
        },
        changeTaxValue(taxValue){
            if ( this.hasStaticFieldTax() ) {
                this.tax = taxValue;
            } else {
                var options = this.model.fields['tax_id'].options;

                for ( var i = 0; i < options.length; i++ ) {
                    if ( options[i][0] == taxValue ) {
                        this.tax = options[i][1].tax;
                    }
                }
            }
        }
    }
}
</script>