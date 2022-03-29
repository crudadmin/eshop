<template>
<div>
    <div class="box box--presets mb-2" v-if="isDeepLevel === false">
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
            @start="model.onDragStart($event)"
            @change="model.onDragChange($event, null, firstLevel)"
            v-bind="model.getDragOptions()"
            handle=".sitetree__item__drag"
            draggable=".--draggableItem">
            <CategoriesTreeItem
                v-for="item in firstLevel"
                level="0"
                :model="model"
                :item="item"
                :items="items"
                :sortable="sortable"
                :put="put"
                :key="item.id" />

            <div slot="footer">
                <CategoriesAddNew :model="model" />
            </div>
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

    created(){
        this.model.hideTab('categories');
    },

    computed: {
        isDeepLevel(){
            return this.model.getParentModels().length > 0;
        },
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