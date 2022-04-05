<?php

namespace AdminEshop\Contracts\Request;

use Admin\Controllers\GettextController;
use EditorMode;
use Store;
use Admin;

class BootstrapRequest
{
    public function request()
    {
        return array_merge(
            [
                'store' => $this->getStoreProperties(),
            ],
            $this->getAdminFeatures()
        );
    }

    public function getBackendEnv()
    {
        return [];
    }

    public function getStoreProperties()
    {
        return [
            'store/setBackendEnv' => $this->getBackendEnv(),
            'store/setSettings' => Store::getSettings()->setBootstrapResponse(),
            'store/setCurrency' => Store::getCurrency(),
            'store/setRounding' => Store::getRounding(),
            'store/setVat' => Store::hasB2B() ? false : true,
            'store/setVats' => Store::getVats(),
            'store/setCountries' => Store::getCountries()->each->setBootstrapResponse(),
        ];
    }

    public function getAdminFeatures()
    {
        return [
            'seo_routes' => $this->getSeoRoutes(),
            'routes' => EditorMode::getVisibleRoutes(),
            'translates' => (new GettextController)->getJson(),
        ];
    }

    private function getSeoRoutes()
    {
        return Admin::getModel('RoutesSeo')->get()->each(function($row){
            $row->setVisible(['url', 'title', 'keywords', 'description', 'metaImageThumbnail']);

            $row->setLocalizedResponse();
        })->toArray();
    }

}

?>