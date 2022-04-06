<?php

namespace AdminEshop\Eloquent\Concerns;

use Exception;

trait SearchableTrait
{
    /**
     * Replaces spaces with full text search wildcards
     *
     * @param string $term
     * @return string
     */
    protected function fullTextWildcards($term)
    {
        // removing symbols used by MySQL
        $reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~'];
        $term = str_replace($reservedSymbols, '', $term);

        $words = explode(' ', $term);

        foreach($words as $key => $word) {
            /*
             * applying + operator (required word) only big words
             * because smaller ones are not indexed by mysql
             */
            if(strlen($word) >= 2) {
                $words[$key] = '+' . $word . '*';
            }
        }

        $searchTerm = implode(' ', $words);

        return $searchTerm;
    }

    public function getSearchableColumns()
    {
        return method_exists($this, 'searchable') ? $this->searchable() : ($this->searchable ?: []);
    }

    /**
     * Scope a query that matches a full text search of term.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFulltextSearch($query, $term, $where = null, $orderBy = null)
    {
        $searchable = $this->getSearchableColumns();
        if ( is_array($searchable) === false || count($searchable) === 0 ){
            throw new Exception('No searchable columns found.');
        }

        $columns = implode(',', $query->getModel()->fixAmbiguousColumn($searchable));

        $terms = $this->fullTextWildcards($term);

        $hasNameField = $this->getField('name') ? true : false;

        $query
            ->selectRaw($this->getModel()->getTable().".*, MATCH ({$columns}) AGAINST (? IN BOOLEAN MODE) AS relevance_score", [$terms])
            ->where(function($query) use ($columns, $terms, $where) {
                $query->whereRaw("MATCH ({$columns}) AGAINST (? IN BOOLEAN MODE)" , $terms);

                if ( is_callable($where) ) {
                    $where($query);
                }
            })
            ->withoutGlobalScope('order')
            ->orderByRaw('relevance_score DESC'.($hasNameField ? ', name ASC' : '').($orderBy ? ', '.$orderBy : null));

        return $query;
    }
}