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
            :group="{ put : put, group : model.table }"
            :list="firstLevel"
            @start="model.onDragStart($event, firstLevel)"
            @end="model.onDragEnd($event, firstLevel)"
            @change="model.onDragChange($event, firstLevel)"
            v-bind="model.getDragOptions()"
            handle=".sitetree__item__drag">

            <CategoriesTreeItem
                v-for="item in firstLevel"
                :model="model"
                :item="item"
                :items="items"
                :sortable="sortable"
                :put="put"
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
            put : true,
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