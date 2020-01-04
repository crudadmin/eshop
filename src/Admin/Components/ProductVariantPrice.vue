<template>
    <div class="form-group" v-show="canShowField">
        <label>{{ field.name }}</label>

        <input type="number" :placeholder="field.placeholder" :name="key" :value="valueOrDefault" @keyup="onChange" class="form-control">
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['key', 'field', 'row'],

    data(){
        return {
            variants_count : 0,
        }
    },

    computed : {
        valueOrDefault(){
            return this.field.value || this.field.default || 0;
        },
        canShowField(){
            return this.row.price_operator && ['abs', 'default'].indexOf(this.row.price_operator) === -1;
        },
    },

    methods : {
        onChange(e){
            this.field.value = e.target.value;
        },
    }
}
</script>