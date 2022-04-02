<?php

namespace AdminEshop\Models\Attribute;

use Admin;
use AdminEshop\Contracts\Concerns\HasUnit;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class Attribute extends AdminModel
{
    use HasUnit;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-11 17:47:15';

    /*
     * Template name
     */
    protected $name = 'Atribúty';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'products';

    protected $reversed = true;

    protected $sluggable = 'name';

    protected $options = [
        'sortby' => [
            'asc' => 'Vzostupne (0-9|A-Z)',
            'desc' => 'Zostupne (Z-A|9-0)',
            'order' => 'Vlastné radenie podľa tabuľky',
        ],
    ];

    public function settings()
    {
        return [
            'title.insert' => 'Nový atribút',
            'title.update' => ':name',
            'columns.id.hidden' => env('APP_DEBUG') == false,
        ];
    }

    public function active()
    {
        return count(config('admineshop.attributes.eloquents', [])) > 0;
    }

    public function reserved()
    {
        return array_filter(
            explode(',', env('ATTR_COLOR_ID')),
        );
    }

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'name' => 'name:Názov atribútu|required'.(Store::isEnabledLocalization() ? '|locale' : ''),
            'unit' => 'name:Merná jednotka / Typ atribútu|belongsTo:attributes_units,:name (:unit)|canEdit|canAdd',
            'title' => 'name:Popis|'.(Store::isEnabledLocalization() ? '|locale' : ''),
            'sortby' => 'name:Zoradiť podľa|type:select|required|default:asc',
            'code' => 'name:Importný kód|inaccessible|max:30|index',
        ];
    }

    public function mutateFields($fields)
    {
        $filtrable = config('admineshop.attributes.filtrable', true);
        $attributesText = config('admineshop.attributes.attributesText', false);
        $attributesVariants = config('admineshop.attributes.attributesVariants', false);

        if ( $filtrable || $attributesText || $attributesVariants ){
            $fields->push(
                Group::inline(array_filter([
                    'filtrable' => $filtrable ? 'name:Filtrovať podľa atribútu|type:checkbox|title:Povoliť atribút vo filtrácii produktov|default:0' : null,
                    'product_info' => $attributesText ? 'name:V skrátenom popise produktu|title:Zobraziť v skátenom popise produktu|type:checkbox|default:0' : null,
                    'variants' => $attributesVariants ? 'name:Definuje variantu produktu|title:Zobrazi sa v detaile produktu možnosť preklikávania medzi priradenými hodnotami atribútu|type:checkbox|default:0' : null,
                ]))->name('Nastavenia atribútu')->id('settings')
            );
        }
    }

    public function scopeWithItemsForProducts($query, $options)
    {
        $query->with([
            'items' => function($query) use ($options) {
                $query->withListingItems($options);
            }
        ]);
    }

    public function loadItemsForProducts($options)
    {
        $this->load([
            'items' => function($query) use ($options){
                $query->withListingItems($options);
            }
        ]);
    }

    /**
     * This columns will be loaded into list of attributes in category response
     *
     * @return  array
     */
    public function getAttributesColumns()
    {
        return array_filter([
            'id', 'name', 'unit_id', 'slug', 'sortby',
            config('admineshop.attributes.filtrable', true) ? 'filtrable' : null,
            config('admineshop.attributes.attributesText', false) ? 'product_info' : null,
            config('admineshop.attributes.attributesVariants', false) ? 'variants' : null,
            $this->isSortable() ? '_order' : null,
        ]);
    }

    /**
     * Can this attribute be displayed in attributesText attribute
     *
     * @return  bool
     */
    public function displayableInTextAttributes()
    {
        //In administration variant list display all attributes when no text attributes is allowed
        if ( config('admineshop.attributes.attributesText', false) === false ){
            return true;
        }

        return $this->getAttribute('product_info') == true;
    }

    public function displayableInVariantsTextAttributes()
    {
        return $this->getAttribute('variants') == true;
    }

    public function setListingResponse()
    {
        $this->append([
            'unitFormat',
        ]);

        $this->items->each->setListingResponse();

        return $this;
    }

    public function setDetailResponse()
    {
        $this->setListingResponse();

        $this->append([
            'unitName',
        ]);

        $this->items->each->setDetailResponse();

        return $this;
    }
}