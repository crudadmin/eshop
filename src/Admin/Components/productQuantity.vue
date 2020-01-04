<template>
    <div class="form-group" v-show="variants_count == 0">
        <label>{{ field.name }}</label>

        <input type="text" :name="key" :value="valueOrDefault" @keyup="onChange" class="form-control">
        <small>v zakladnej variante</small>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['key', 'field'],

    data(){
        return {
            variants_count : 0,
        }
    },

    ready(){
        this.$on('rows-changed', function(item){
            if ( item.model == 'products_variants' )
                this.variants_count = item.count;
        });
    },

    computed : {
        valueOrDefault(){
            return this.field.value || this.field.default;
        },
    },

    methods : {
        onChange(e){
            this.field.value = e.target.value;
        },
    }
}
</script>