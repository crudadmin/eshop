<template>
    <div>
        <div class="form-group">
            <label>Vyberte kategóriu</label>
            <select class="form-control" v-model="category">
                <option v-for="category in data.categories" :value="category.id">{{ category.name }}</option>
            </select>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows', 'request', 'data'],

    data(){
        return {
            category : null,
        }
    },

    created(){
        this.setRequestData();
    },

    watch : {
        category(){
            this.setRequestData();
        },
    },

    methods: {
        setRequestData(){
            this.request.category = this.category;
        },
    },
}
</script>