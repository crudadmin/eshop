<template>
    <div>
        <div class="form-group">
            <label>Typ zľavy</label>
            <select class="form-control" v-model="discount_operator">
                <option v-for="(option, value) in data.discount_operator" :value="value">{{ option }}</option>
            </select>
        </div>

        <div class="form-group" v-if="discount_operator != 'default'">
            <label>Výška zľavy</label>
            <input type="decimal" v-model="discount" class="form-control">
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows', 'request', 'data'],

    data(){
        return {
            discount_operator : 'default',
            discount : null,
        }
    },

    created(){
        this.setRequestData();
    },

    watch : {
        discount_operator(){
            this.setRequestData();
        },
        discount(){
            this.setRequestData();
        }
    },

    methods: {
        setRequestData(){
            this.request.discount_operator = this.discount_operator;
            this.request.discount = this.discount;
        },
    },
}
</script>