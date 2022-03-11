<template>
    <div>
        <label>{{ __('Odoslať testovací stav k objednávke:') }}</label>
        <input type="text" name="order_number" value="data.order_number" v-model="request.order_number" class="form-control">
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['request', 'data'],
    mounted(){
        this.request.order_number = this.data.order_number;
    },
}
</script>