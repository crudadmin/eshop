<template>
    <div class="form-group" v-show="canShowField">
        <label style="display: block">{{ field.name }}</label>

        <input type="text" id="ppl-date" :placeholder="field.placeholder" :name="key" :value="valueOrDefault" @keyup="onChange" class="form-control">
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
        this.bindDatePickers();
    },

    computed : {
        valueOrDefault(){
            return this.field.value || this.field.default;
        },
        canShowField(){
            return this.row.type && ['ppl'].indexOf(this.row.type) > -1;
        },
    },

    methods : {
        bindDatePickers(){
            $('#ppl-date').datetimepicker({
                lang: this.$root.locale,
                format: 'd.m.Y',
                timepicker: false,
                datepicker: true,
                scrollInput: false,
                scrollMonth: false,
                scrollYear: false,
                inline : false,
                onChangeDateTime: function(current_date_time) {
                    var date = moment(current_date_time).format('D.M.Y');

                    this.field.value = date;
                }.bind(this)
            });
        },
        onChange(e){
            this.field.value = e.target.value;
        },
    }
}
</script>