<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Contracts\Feed\Heureka\HeurekaFeed;
use AdminEshop\Contracts\Order\Mutators\AssignedProductMutator;
use Admin\Fields\Group;
use OrderService;
use Store;

trait HasProductFields
{
    public function getGeneralFields()
    {
        return Group::inline([
            Group::fields(array_merge(
                [
                    Group::fields([
                        'name' => 'name:Názov produktu|limit:65|required'.(Store::isEnabledLocalization() ? '|locale' : '|index').'|'.(in_array('name', $this->getSearchableColumns()) && !Store::isEnabledLocalization() ? 'fulltext' : ''),
                    ])->id('names'),
                    Group::fields([
                        'image' => 'name:Obrázok|type:file|image',
                    ])->id('images')
                ],
                Store::hasCategories()
                    ? ['categories' => 'name:Kategória|belongsToMany:categories,name|component:selectParentCategories|canAdd|removeFromFormIfNot:product_id,NULL'] : [],
                ['attributes_items' => 'name:Atribúty|belongsToMany:attributes_items,:attribute_name - :name']
            ))->id('general')->name('Základne nastavenia'),
            Group::fields([
                'product_type' => 'name:Typ produktu|type:select|option:name|index|default:regular|hideFromFormIf:product_type,variant|sub_component:setProductType|required',
                Group::inline([
                    'ean' => 'name:EAN|hidden',
                    'code' => 'name:Kód produktu|index',
                    'code_pairing' => 'name:Párovací kód produktu|inaccessible',
                ])->attributes('hideFromFormIf:product_type,variants'),
            ])->name('Identifikátory produkty')->id('identifiers'),
        ])->icon('fa-pencil')->id('general-tab');
    }

    public function getPriceFields()
    {
        return Group::tab([
            'Cena' => Group::fields([
                Group::fields([
                    'vat' => 'name:Sazba DPH|belongsTo:vats,:name (:vat%)|defaultByOption:default,1|canAdd|hidden',
                    'price' => 'name:Cena bez DPH|type:decimal|decimal_length:'.config('admineshop.prices.decimals_places').'|default:0|component:PriceField',
                ])->add('required_if:product_type,'.implode(',', Store::orderableProductTypes()))
            ])->id('price')->width(8),
            'Zľava' => Group::fields([
                'discount_operator' => 'name:Typ zľavy|type:select|hidden',
                'discount' => 'name:Výška zľavy|type:decimal|decimal_length:'.config('admineshop.prices.decimals_places').'|hideFieldIfIn:discount_operator,NULL,default|required_if:discount_operator,'.implode(',', array_keys(operator_types())).'|hidden',
            ])->id('discount')->width(4),
        ])->icon('fa-money')->id('price-tab')->name('Cena')->attributes('hideFromFormIf:product_type,variants')->add('removeFromFormIf:product_type,variants');
    }

    public function getDescriptionFields()
    {
        return Group::tab([
            Group::fields([
                'weight' => 'name:Váha ('.(config('admineshop.product.weight_unit') == 'kilograms' ? 'kg' : 'gramov').')|type:decimal|hidden',
            ])->if(config('admineshop.product.weight_unit')),
            'description' => 'name:Popis produktu|type:editor|hidden'.(Store::isEnabledLocalization() ? '|locale' : ''),
        ])->icon('fa-file-text-o')->id('description-tab')->name('Popis');
    }

    public function getWarehouseFields()
    {
        return Group::tab([
            'stock_quantity' => 'name:Sklad|type:integer|default:0|hideFromFormIf:product_type,variants',
            Group::fields([
                    'stock_type' => 'name:Možnosti skladu|default:default|type:select|index',
                    'stock_sold' => 'name:Text dostupnosti tovaru s nulovou skladovosťou|hideFromFormIfNot:stock_type,everytime'
            ])->attributes('hideFromFormIf:product_type,variant')->if(config('admineshop.stock.store_rules', true)),
        ])->icon('fa-bars')->id('warehouse-tab')->add('hidden')->name('Sklad');
    }

    public function getOtherSettingsFields()
    {
        return Group::tab([
            Group::fields([ //temporary fix for vue error
                Group::fields([
                    'created_at' => 'name:Vytvorené dňa|default:CURRENT_TIMESTAMP|type:datetime|disabled',
                    'published_at' => 'name:Publikovať od|default:CURRENT_TIMESTAMP|type:datetime',
                ]),
                Group::fields([
                    'heureka_name' => 'name:Názov pre heureku|hidden'
                ])->if(HeurekaFeed::isEnabled()),
                Group::fields([
                    'assigned_product' => 'name:S produktom vložiť do košíka aj|belongsTo:products,name|hidden',
                ])->if(OrderService::hasMutator(AssignedProductMutator::class)),
            ]),
        ])->id('otherSettings')->icon('fa-gear')->name('Ostatné nastavenia');
    }
}