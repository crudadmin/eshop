<?php

namespace AdminEshop\Http\Resources;

use Admin\Eloquent\AdminModel;
use Facades\AdminEshop\Contracts\Nuxt;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NuxtApiResponse extends ResourceCollection
{
    public static $wrap = null;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct()
    {
        parent::__construct(func_get_args());
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $response = [
            'bundle_hash' => Nuxt::getBundleKey(),
            'data' => [],
        ];

        foreach ($this->resource as $key => $data) {
            if ( is_array($data) ) {
                foreach ($data as $k => $value) {
                    if ( $value instanceof JsonResource ){
                        $data[$k] = $value->toResponse($request)->getData(true);
                    }
                }

                $response['data'] = array_merge($response['data'], $data);
            }

            else if ( $data instanceof AdminModel ){
                if ( isset($response['model']) ){
                    $response['model'] = [];
                }

                $modelName = class_basename(get_class($data));
                $modelName = strtolower(substr($modelName, 0, 1)).substr($modelName, 1);

                $response['model'][$modelName] = $data->toArray();
            }

            if ( $data instanceof JsonResource ){
                $data = $data->toResponse($request)->getData(true);

                foreach ($data as $key => $value) {
                    if ( array_key_exists($key, $response) ) {
                        $response[$key] = array_merge($response[$key], $value);
                    } else {
                        $response[$key] = $value;
                    }
                }
            }
        }

        return $response;
    }
}
