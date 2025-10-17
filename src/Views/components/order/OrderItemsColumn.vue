<template>
<div @mouseenter="loadProducts" ref="element" data-bs-template='<div class="tooltip tooltip-products" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>' data-toggle="tooltip" data-bs-html="true" data-bs-delay="5000" :title="products" :key="loaded ? 1 : 0">
    {{ valueMutated }}
</div>
</template>

<script type="text/javascript">
export default {
    props : ['valueMutated', 'value', 'field', 'row', 'model'],

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

            let response = await useAxios().$get('/orders/'+this.row.id+'/items');

            $(this.$refs.element).tooltip('hide');

            this.products = response.map(item => {
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