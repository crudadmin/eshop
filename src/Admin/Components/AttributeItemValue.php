<template>
    <div class="form-group" v-show="row.attribute_id">
        <label>{{ field.name }}</label>

        <select :name="key" class="form-control">
            <option value="">Vyberte hodnotu atribútu</option>
            <option :value="option.id" v-for="option in options">{{ option.name }}</option>
        </select>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['key', 'field', 'row'],

    data(){
        return {
            attributes : [],
            variants : [],
        }
    },

    ready(){
        this.$dispatch('sendParentRow');

        this.$on('rows-changed', function(items){
            if ( items.slug == 'products_variants_attributes' )
                this.attributes = items.rows;
        });
    },

    events: {
        getParentRow(item){
            if ( item.slug == 'products_variants' ){
                this.variants = item.rows;
            }
        },
    },

    computed : {
        valueOrDefault(){
            return this.field.value || this.field.default;
        },
        options(){
            var options = this.field.options.map(function(item){
                return item[1];
            }).filter(function(item){
                if ( item.attribute_id != this.row.attribute_id )
                    return false;

                //Remove selected attributes
                for ( var key in this.attributes )
                    if ( this.attributes[key].item_id == item.id )
                        return false;

                //Remove selected attributes
                for ( var key in this.variants )
                    if ( this.variants[key].attributes_ids.indexOf(item.id) > -1 )
                        return false;

                return true;
            }.bind(this))

            return options;
        },
    },

    methods : {
        onChange(e){
            this.field.value = e.target.value;
        },
    }
}
</script>