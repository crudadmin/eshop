<template>
<div class="sitetree__item__wrapper --draggableItem">
    <div class="sitetree__item">
        <div class="sitetree__item__drag" v-if="sortable && row.id">
            <i class="fa fa-grip-vertical"></i>
        </div>
        <div class="sitetree__item__inputs">
            <h6 class="mb-0">
                {{ getLocaleFieldValue(item.name) }}
                <small>({{ __('%s podkategórii').replace('%s', nextLevel.length) }} / {{ __('%s produktov').replace('%s', item.products_count||0) }})</small>
            </h6>
        </div>
        <div class="sitetree__item__actions">
            <button
                class="btn btn-sm"
                :class="{ 'btn-primary' : showSubTree, 'btn-default' : !showSubTree }"
                @click="showSubTree = !showSubTree"
                v-if="canDisplayNextLevel"
            >
                <i class="fa" :class="{ 'fa-angle-down' : !showSubTree, 'fa-angle-up' : showSubTree }"></i>
            </button>

            <button
                class="btn btn-sm btn-default"
                @click="addNewSubcategory()"
                :title="__('Pridať podkategóriu')"
                data-toggle="tooltip"
                v-if="model.insertable"
            >
                <i class="fa fa-plus"></i>
            </button>

            <button
                data-toggle="tooltip"
                :title="_('Upraviť podkategóriu')"
                class="btn btn-sm"
                :class="{'btn-success' : model.isActiveRow(row), 'btn-default' : !model.isActiveRow(row) }"
                @click="model.selectRow(row)"
            >
                <i class="far fa-edit"></i>
            </button>

            <buttons-action
                v-for="(button, buttonKey) in model.getButtonsForRow(row)"
                :key="buttonKey"
                :button="button"
                :row="row"
                :buttonKey="buttonKey"
                :model="model"/>

            <publish-button
                v-if="row.id && model.canUnpublishRow(row)"
                :model="model"
                :row="item" />

            <button
                v-if="row.id"
                class="btn btn-sm btn-danger"
                @click="removeItem(row)"
            >
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>
    <div class="sitetree__subtree" v-if="showSubTree">
        <component
            :is="sortable ? 'draggable' : 'div'"
            :group="{ put : put, group : model.table }"
            :list="nextLevel"
            @start="model.onDragStart($event)"
            @change="model.onDragChange($event, item, nextLevel)"
            v-bind="model.getDragOptions()"
            handle=".sitetree__item__drag"
            draggable=".--draggableItem">
            <CategoriesTreeItem
                v-for="subItem in nextLevel"
                :model="model"
                :item="subItem"
                :parentRow="item"
                :sortable="sortable"
                :put="put"
                :items="items"
                :level="level + 1"
                :key="subItem.id" />

            <div slot="footer" v-if="nextLevel.length == 0">
                <CategoriesAddNew :model="model" :item="item" />
            </div>
        </component>
    </div>
</div>
</template>


<script type="text/javascript">
import { mapState } from 'vuex';
import _ from 'lodash';
import draggable from 'vuedraggable'

export default {
    name : 'CategoriesTreeItem',

    props : ['item', 'parentRow', 'items', 'sortable', 'put', 'level'],

    components : {
        draggable,
    },

    data(){
        let model = this.getActiveModel('categories');

        return {
            showSubTree : false,
            row : _.cloneDeep(this.item||model.emptyRowInstance()),
            model : model,

            errors : [],
        }
    },

    watch: {

    },

    computed: {
        nextLevel(){
            if ( !this.item ){
                return [];
            }

            return _.filter(this.items, {
                category_id : this.item.id
            });
        },
        canDisplayNextLevel(){
            if ( !this.row?.id || this.model.insertable === false ){
                return;
            }

            return this.level <= this.model.getSettings('recursivity.max_depth');
        }
    },

    methods: {
        removeItem(row){
            this.model.removeRow(row.id);
        },
        addNewSubcategory(){
            this.model.createNewEntry();
            this.model.getRow().category_id = this.item?.id;
        }
    }
}
</script>

<style lang="scss" scoped>

</style>