<?php

namespace AdminEshop\Admin\Buttons;

use AdminEshop\Eloquent\Concerns\HasCategoryTree;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use OrderService;
use Store;

class SetProductsCategory extends Button
{
    use HasCategoryTree;

    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct($row)
    {
        //Name of button on hover
        $this->name = _('Hromadné priradenie kategórie');

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-bars';

        $this->type = 'action';

        $this->active = true;
    }

    /*
     * Ask question with form before action
     */
    public function question($row)
    {
        return $this->title(_('Vyberte kategóriu pre vybrané produkty:'))
                    ->component('SetProductCategories', [
                        'categories' => $this->getCategoriesOptions(),
                    ])
                    ->type('default');
    }

    /*
     * Firing callback on press button
     */
    public function fireMultiple($rows)
    {
        if ( !($item = collect($this->getCategoriesOptions())->firstWhere('id', request('category'))) ){
            return $this->error(_('Nebola zvolená kategória.'));
        }

        if ( $rows->count() ){
            $rows->each(function($product) use ($item) {
                $this->setProductCategory($product, $item);
            });
        }

        return $this->success(_('Kategória bola pre vybrané produkty úspešne priradená.'));
    }

    public function setProductCategory($product, $item)
    {
        $product->categories()->sync($item['tree']);
    }
}