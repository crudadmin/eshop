<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows', 'request'],

    mounted(){
        this.setGroupsVisibility();

        this.$watch('row.multiple_locations', this.setGroupsVisibility)
    },

    methods: {
        setGroupsVisibility(){
            this.model.setTabVisibility('deliveries_locations', this.row.multiple_locations ? true : false);
        },
    },
}
</script>