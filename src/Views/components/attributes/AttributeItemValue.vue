<template>
    <div class="form-group" :class="{ disabled : field.disabled || field.readonly }">
        <label>{{ field.name }} <span v-if="unit && unit.unit">{{ unit ? '('+unit.unit+')' : '' }}</span></label>

        <input type="hidden" name="unit_id" v-if="unit" :value="unit.id">

        <input
            :type="fieldType"
            :name="name"
            :value="value"
            @keyup="onChange"
            :step="unitFormat == 'decimal' ? 'any' : ''"
            :placeholder="field.placeholder||field.name"
            :readonly="field.readonly||false"
            :disabled="field.disabled||false"
            class="form-control">
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['name', 'field', 'row', 'model', 'value'],

    data(){
        return {

        }
    },

    mounted(){
        this.bootUnitType();
    },

    watch : {
        unitFormat(format){
            this.bootUnitType();
        }
    },

    computed : {
        unit(){
            let parentModel = this.model.getParentModel(),
                unitId;

            if ( parentModel && parentModel.table == 'attributes' && this.model.isOpenedRow() ){
                unitId = this.row.unit_id;
            }

            if ( parentModel.getRow().unit_id ){
                unitId = parentModel.getRow().unit_id;
            }

            return _.find(this.model.getData('store_units'), {
                id : parseInt(unitId)
            });
        },
        unitFormat(){
            if ( this.unit ){
                return this.unit.format;
            }
        },
        isNumeric(){
            return ['number', 'decimal'].indexOf(this.unitFormat) > -1
        },
        fieldType(){
            if ( this.isNumeric ){
                return 'number';
            }

            return 'text';
        },
    },

    methods : {
        //Update input value
        onChange(e){
            this.$parent.changeValue(e);
        },
        bootUnitType(){
            //Colors
            this.model.setColumnVisibility('color', this.unitFormat == 'color');
            this.model.removeFromForm('color', this.unitFormat != 'color');
        }
    }
}
</script>