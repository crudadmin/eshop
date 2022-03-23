<template>
<div>
    <div class="box box--presets mb-2">
        <div class="box-header box-header--actions">
            <div class="box-header__left">
                <div>
                    <h1 class="box-header__title">{{ model.name }}</h1>
                    <p class="box-header__description" v-if="model.title">{{ model.title }}</p>
                </div>
            </div>

            <div class="box-header__right">

            </div>
        </div>

        <div class="box-body">
        <component
            :is="sortable ? 'draggable' : 'div'"
            :group="{ put : false }"
            @start="model.onDragStart($event)"
            @end="model.onDragEnd($event, firstLevel)"
            v-bind="model.getDragOptions()"
            handle=".sitetree__item__drag">

            <CategoriesTreeItem
                v-for="item in firstLevel"
                :item="item"
                :items="items"
                :sortable="sortable"
                :key="item.id" />
            </component>
        </div>
    </div>
</div>
</template>

<script type="text/javascript">
export default {
    props : ['model'],

    data(){
        return {
            sortable : true,
        }
    },

    computed: {
        items(){
            return this.model.getRows({ withAllRows : true });
        },
        firstLevel(){
            let items = _.filter(this.items, {
                category_id : null
            });

            return items;
        },
        firstLevelDisabledTypes(){
            return ['url', 'model'];
        },
    }
}
</script>