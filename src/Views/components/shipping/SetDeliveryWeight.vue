<template>
    <div>
        <label>{{ __('Zadajte váhu balíka (kg):') }}</label>
        <input type="decimal" step=".2" placeholder="0.5" v-model="request.weight" class="form-control">
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows', 'request', 'data'],

    mounted(){
        if ( this.data.weight ){
            this.$set(this.request, 'weight', this.data.weight);
        }
    },

    data(){
        return {

        }
    },
}
</script>