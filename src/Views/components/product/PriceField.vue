<template>
    <div class="form-group" :class="{ disabled : disabled || readonly }">
        <div class="row">
            <div class="col-md-4">
                <label>{{ field.name }} <span class="required" v-if="required">*</span></label>
                <input type="number" step=".01" :name="field_key" :value="valueOrDefault" @keyup="onChange" class="form-control" :readonly="disabled || readonly">
            </div>
            <div class="col-md-4">
                <label>DPH</label>
                <input type="number" step=".01" :value="vatSize" disabled class="form-control">
            </div>
            <div class="col-md-4">
                <label>Cena s DPH</label>
                <input type="number" step=".01" :name="field_key+'_vat'" :value="vatPrice" @keyup="changePrice" @change="recalculateWithoutVatPrice" class="form-control" :readonly="disabled || readonly">
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['field_key', 'field', 'row', 'model', 'required', 'disabled', 'readonly'],

    data(){
        return {
            vat : 0,
        }
    },

    mounted(){
        //On vat value change
        this.onVatChange();

        //Bind default vat value
        this.changeVatValue(this.row[this.getVatFieldKey()]);
    },

    computed: {
        valueOrDefault(){
            //We want rewrite value only if is initial null state
            if ( _.isNil(this.field.value) ) {
                return this.field.default||0;
            }

            return this.field.value;
        },
        vatSize(){
            return (this.field.value * (this.vat / 100)).toFixed(2);
        },
        vatPrice(){
            return (this.field.value * (1 + (this.vat / 100))).toFixed(2);
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
        hasStaticFieldVat(){
            return this.model.fields[this.getFieldPrefix()+'vat'] ? true : false;
        },
        getVatFieldKey(){
            var field = this.getFieldPrefix()+(this.hasStaticFieldVat() ? 'vat' : 'vat_id');

            return this.model.fields[field] ? field : 'vat_id';
        },
        onChange(e){
            this.field.value = e.target.value;
        },
        changePrice : _.debounce(function(e){
            this.recalculateWithoutVatPrice(e);
        }, 1500),
        recalculateWithoutVatPrice(e){
            this.field.value = (e.target.value / (1 + (this.vat / 100))).toFixed(2);
        },
        onVatChange(){
            this.$watch('row.'+this.getVatFieldKey(), this.changeVatValue);
        },
        changeVatValue(vatValue){
            if ( this.hasStaticFieldVat() ) {
                this.vat = vatValue;
            } else {
                var options = this.model.fields['vat_id'].options;

                for ( var i = 0; i < options.length; i++ ) {
                    if ( options[i][0] == vatValue ) {
                        this.vat = options[i][1].vat;
                    }
                }
            }
        }
    }
}
</script>