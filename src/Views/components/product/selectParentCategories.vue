<script type="text/javascript">
export default {
    props : ['model', 'field'],
    mounted(){
        this.$watch('field.value', (value, oldValue) => {
            if ( this.toggling === true ){
                return;
            }

            this.toggling = true;

            if ( (value||[]).length > (oldValue||[]).length ) {
                let addedCategoryId = _.xor(oldValue, value)[0];

                this.addParentCategories(addedCategoryId)
            }

            if ( (value||[]).length < (oldValue||[]).length ) {
                let removedCategoryId = _.xor(value, oldValue)[0];

                this.removeChildCategories(removedCategoryId)
            }

            setTimeout(() => {
                this.toggling = false;
            }, 100);
        })
    },
    methods : {
        addParentCategories(id){
            let addedOption = this.field.options.filter(option => {
                return option[0] == id;
            })[0][1];

            let values = _.xor(_.cloneDeep(this.field.value), [id]);

            //Add all parents categories
            if ( addedOption.tree.length > 1 ) {
                addedOption.tree.slice(0, -1).forEach(parentId => {
                    if ( values.indexOf(parentId) == -1 ) {
                        values.push(parentId);
                    }
                });

            }

            values.push(id);

            this.model.setValue('categories', values);
        },
        removeChildCategories(id){
            let values = _.xor(_.cloneDeep(this.field.value), [id]);

            //Add all parents categories
            let toRemove = this.field.options.filter(option => {
                return option[1].tree.indexOf(id) > -1;
            })
                .map(option => option[0])
                .filter(id => values.indexOf(id) > -1);

            values = _.xor(values, toRemove);

            this.model.setValue('categories', values);
        }
    }
}
</script>