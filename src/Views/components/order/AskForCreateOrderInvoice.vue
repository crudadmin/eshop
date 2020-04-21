<template>
    <div>
        <div class="form-group">
            <label>Aký typ dokladu si prajete vygenerovať?</label>
            <select class="form-control" v-model="invoice_type">
                <option value="">Vyberte typ dokladu</option>
                <option value="proform">Proforma</option>
                <option value="invoice">Faktúra</option>
                <option value="return">Dobropis</option>
            </select>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows', 'request'],

    data(){
        return {
            invoice_type : '',
        }
    },

    mounted(){
        this.setRequestData();
    },

    watch: {
        invoice_type(){
            this.setRequestData()
        },
    },

    methods: {
        setRequestData(){
            this.request.invoice_type = this.invoice_type;
        },
    },
}
</script>