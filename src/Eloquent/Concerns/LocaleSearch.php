<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Eloquent\Concerns\SearchableTrait;
use DB;

trait LocaleSearch
{
    use SearchableTrait;

    public function searchableIndexes()
    {
        $localeSearch = $this->getProperty('localeSearch');

        $defaultKeys = ['name'];

        if ( $this->getField('meta_title') ){
            $defaultKeys[] = 'meta_title';
        }

        if ( $this->getField('meta_keywords') ){
            $defaultKeys[] = 'meta_keywords';
        }

        return is_array($localeSearch) ? $localeSearch : $defaultKeys;
    }

    public function searchable()
    {
        return ['fulltext_index'];
    }

    /**
     * Search by given locale key or search by meta keywords
     *
     * @param  Builder  $query
     * @param  string  $key
     * @param  string  $searchQuery
     */
    public function scopeLocaleSearchable($query, $key, $searchQuery)
    {
        $query->where(function($query) use ($key, $searchQuery) {
            $query->localeSearch($key, $searchQuery);
        });

        //If has seo module enabled
        if ( $query->getModel()->getField('meta_keywords') ) {
            $query->orWhere(function($query) use ($searchQuery) {
                $query->localeKeywordsSearch($searchQuery);
            });
        }
    }

    public function scopeUniSearch($query, $term, $fuzzy = true)
    {
        $query->where(function($query) use ($term, $fuzzy) {
            $query->fulltextSearch($term);

            if ( $fuzzy == true ) {
                $fuzzyModel = clone $query->getModel();
                $fuzzyModel->table = null;

                //Add basic fuzzy support
                $fuzzyIds = $fuzzyModel->select('id')->whereFuzzy('fulltext_index', $term)->limit(5)->pluck('id')->toArray();

                $query->orWhereIn($query->getModel()->getTable().'.id', $fuzzyIds);
            }
        });
    }

    public function setSearchIndex()
    {
        if ( $index = $this->getSearchIndex() ){
            $this->fulltext_index = $index;
        }

        return $this;
    }

    public function getSearchIndex()
    {
        $localeSearch = $this->getProperty('localeSearch');

        if ( $localeSearch === null || !$localeSearch ){
            return;
        }

        $totalLimit = 65500;

        $fields = $this->searchableIndexes();
        $index = [];

        foreach ($fields as $key) {
            $value = $this->getAttribute($key);

            $string = is_array($value) ? implode(' ', array_map(function($item) use ($value, $totalLimit) {
                return substr($item, 0, round($totalLimit / count($value)));
            }, $value)) : $value;

            $string = str_replace($this->reservedSymbols, '', $string);

            $index[] = $string;
        }

        //Remove similar values
        $index = array_filter(array_unique($index));
        $index = implode(' ', $index);

        return substr(trim($index), 0, $totalLimit);
    }
}
