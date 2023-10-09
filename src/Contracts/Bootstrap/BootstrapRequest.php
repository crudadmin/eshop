<?php

namespace AdminEshop\Contracts\Bootstrap;

use Admin;
use AdminEshop\Middleware\SetStoreLanguage;
use Admin\Controllers\GettextController;
use EditorMode;
use Localization;
use Store;

class BootstrapRequest
{
    public function request()
    {
        return array_merge(
            [
                'store' => $this->getStoreProperties(),
            ],
            [
                'crudadmin' => $this->getAdminFeatures()
            ],
        );
    }

    public function getBackendEnv()
    {
        return [
            'APP_ENV' => env('APP_ENV'),
        ];
    }

    public function getStoreProperties()
    {
        return [
            'store/setBackendEnv' => $this->getBackendEnv(),
            'store/setSettings' => Store::getSettings()->setBootstrapResponse(),
            'store/setCurrency' => Store::getCurrency()->setResponse(),
            'store/setRounding' => Store::getRounding(),
            'store/setDecimalPlaces' => Store::getDecimalPlaces(),
            'store/setVat' => Store::hasB2B() ? false : true,
            'store/setVats' => Store::getVats(),
            'store/setCountries' => Store::getCountries()->each->setBootstrapResponse(),
            'store/setCategories' => $this->getCategories(),
        ];
    }

    public function getAdminFeatures()
    {
        return [
            'seo_routes' => $this->getSeoRoutes(),
            'routes' => EditorMode::getVisibleRoutes(),
            'translates' => $this->getJsonTranslations(),
            'languages' => $this->getLanguages(),
        ];
    }

    public function getSeoRoutes()
    {
        if ( !config('admin.seo') ){
            return [];
        }

        return Admin::getModel('RoutesSeo')->get()->each(function($row){
            $row->setVisible(['url', 'title', 'keywords', 'description', 'metaImageThumbnail']);

            $row->setLocalizedResponse();
        })->toArray();
    }

    public function getCategories()
    {
        return [];
    }

    public function getLanguages()
    {
        return Localization::getLanguages()->each->setResponse();
    }

    public function getJsonTranslations()
    {
        return Localization::getJson();
    }

    public function getCacheKey()
    {
        return 'store.bootstrap.'.(SetStoreLanguage::getHeaderLocale() ?: 'default');
    }
}

?>