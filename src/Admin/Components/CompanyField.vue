<template>
    <div class="form-group">
        <label>{{ field.name }}</label>

        <input type="text" :name="key" :value="valueOrDefault" :placeholder="field.name" @keyup="onChange" class="form-control js-company-field">
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

    ready(){
        this.$watch('row.type', function(type){
            $('.js-company-field').parents('.nav-tabs-custom').each(function(){
                $(this).find('div[data-field="company_id"]')[type == 'delivery' ? 'hide' : 'show']();
                $(this).find('div[data-field="company_tax_id"]')[type == 'delivery' ? 'hide' : 'show']();
            })
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