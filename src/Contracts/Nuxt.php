<?php

namespace AdminEshop\Contracts;

class Nuxt
{
    public function getBundleKey()
    {
        if ( !($nuxtPath = env('NUXT_BASEPATH')) ){
            return;
        }

        $clientAppPath = $nuxtPath.'/.nuxt/dist/client';

        //If path does not exists (in dev mode path also does not exists)
        if ( file_exists($clientAppPath) == false ){
            return;
        }

        $jsFiles = array_filter(scandir($clientAppPath), function($file){
            return substr($file, -2) == 'js';
        });

        //If no build files ara available, we does not want force APP to refresh, probably this is onbuild state where
        //app is being builded right now
        if ( count($jsFiles) == 0 ) {
            return;
        }

        return md5(implode(';', $jsFiles));
    }
}

?>