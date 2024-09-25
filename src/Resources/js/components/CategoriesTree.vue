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

                <div class="box-header__right"></div>
            </div>

            <div class="box-body">
                <component
                    tag="div"
                    @start="model.onDragStart($event)"
                    @change="model.onDragChange($event, null, firstLevel)"
                    v-bind="model.getDragOptions()"
                    :is="sortable ? 'draggable' : 'div'"
                    :group="{ put: put, group: model.table }"
                    :list="firstLevel"
                    handle=".sitetree__item__drag"
                    draggable=".--draggableItem"
                    itemKey="id"
                >
                    <template #item="{ element }">
                        <CategoriesTreeItem
                            :level="0"
                            :model="model"
                            :item="element"
                            :items="items"
                            :sortable="sortable"
                            :put="put"
                            :key="element.id"
                        />
                    </template>

                    <template #footer>
                        <CategoriesAddNew :model="model" />
                    </template>
                </component>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    props: ['model'],

    data() {
        return {
            put: true,
            sortable: true,
        };
    },

    created() {
        this.model.hideTab('categories');
    },

    computed: {
        isDeepLevel() {
            return this.model.getParentModels().length > 0;
        },
        items() {
            return this.model.getRowsSorted({ withAllRows: true });
        },
        firstLevel() {
            let items = _.filter(this.items, {
                category_id: null,
            });

            return items;
        },
        firstLevelDisabledTypes() {
            return ['url', 'model'];
        },
    },
};
</script>
