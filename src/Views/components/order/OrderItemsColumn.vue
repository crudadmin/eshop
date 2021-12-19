<template>
<div @mouseenter="loadProducts" ref="element" data-template='<div class="tooltip tooltip-products" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>' data-toggle="tooltip" data-html="true" :title="products" :key="loaded ? 1 : 0">
    {{ mutatedValue }}
</div>
</template>

<script type="text/javascript">
export default {
    props : ['mutatedValue', 'value', 'field', 'row', 'model'],

    data(){
        return {
            loaded : false,
            products : 'Načitávame...',
        };
    },

    methods : {
        async loadProducts(){
            if ( this.loaded == true ){
                return;
            }

            let response = await this.$http.get('/admin/orders/'+this.row.id+'/items');

            $(this.$refs.element).tooltip('hide');

            this.products = response.data.map(item => {
                return `
                <div class="orderItemTooltip">
                    <strong class="--quantity">${item.quantity}x </strong>
                    <span class="--name">${item.name}</span>
                    <strong class="--price">${item.price_total_vat}</strong>
                </div>`;
            }).join('');

            this.loaded = true;

            //Testing
            // this.$nextTick(() => {
            //     $(this.$refs.element).tooltip('show');
            // });
        }
    }
}
</script>