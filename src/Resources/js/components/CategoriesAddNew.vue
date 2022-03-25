<template>
<div class="sitetree__item__wrapper --addNew">
    <div class="sitetree__item">
        <a
            data-toggle="tooltip"
            @click.prevent="addNewSubcategory()"
            :title="_('Pridať podkategóriu')"
            href="#"
        >
            <i class="fa fa-plus"></i>

            {{ item ? __('Pridajte do %s').replace('%s', getLocaleFieldValue(item.name)) : __('Pridajte hlavnú kategóriu') }}
        </a>

        <p>&nbsp;{{ __('alebo pretiahnite existujúcu.') }}</p>
    </div>
</div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'item'],
    methods: {
        addNewSubcategory(){
            this.model.createNewEntry();
            this.model.getRow().category_id = this.item?.id;
        }
    }
}
</script>

<style lang="scss" scoped>
button {
    margin-right: 0.5rem;
}

.sitetree__item {
    background: transparent;
    border: 2px dashed #f9f9f9;
    align-items: center;
    justify-content: center;

    p {
        margin-bottom: 0;
    }
}
</style>