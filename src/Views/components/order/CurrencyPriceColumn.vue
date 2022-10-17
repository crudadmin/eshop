<template>
    <div style="white-space: nowrap;">{{ value }} {{ currency }}</div>
</template>

<script type="text/javascript">
export default {
    props : ['row', 'value'],
    computed: {
        currency(){
            if ( this.row.currency_char ){
                return this.row.currency_char;
            }

            //In order items fetch currency from parent model.
            let OrderModel = this.getActiveModel('orders'),
                OrderRow = OrderModel ? (OrderModel.getRow()||{}) : {};

            return OrderRow.currency_char;
        }
    }
}
</script>