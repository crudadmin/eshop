<template>
    <div>
        <label>Zadajte váhu balíka (kg):</label>
        <input type="decimal" step=".2" placeholder="0.5" v-model="request.weight" class="form-control">
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows', 'request', 'data'],

    data(){
        return {

        }
    },
}
</script>