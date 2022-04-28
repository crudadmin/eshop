<template>
    <div>
        <div class="form-group">
            <label>{{ __('Aký typ dokladu si prajete vygenerovať?') }}</label>
            <select class="form-control" v-model="invoice_type">
                <option value="">{{ __('Vyberte typ dokladu') }}</option>
                <option value="proform">{{ __('Proforma') }}</option>
                <option value="invoice">{{ __('Faktúra') }}</option>
                <option value="return">{{ __('Dobropis') }}</option>
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