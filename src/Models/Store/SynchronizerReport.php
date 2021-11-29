<?php

namespace AdminEshop\Models\Store;

use Admin;
use AdminEshop\Contracts\Synchronizer\Synchronizer;
use Facades\AdminEshop\Contracts\Synchronizer\Synchronizer as SynchronizerFacade;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use League\Flysystem\Exception;

class SynchronizerReport extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-04-10 17:07:05';

    /*
     * Template name
     */
    protected $name = 'História synchronizácii';

    protected $publishable = false;

    protected $sortable = false;

    protected $group = 'store';

    protected $insertable = false;

    /*
     * Automatic form and database generator by fields list
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            Group::fields([
                'name' => 'name:Názov synchronizácie|index',
                'messages' => 'name:Úspešna aktivita|type:longtext|required',
                'errors' => 'name:Chyby|type:longtext|required',
                'duration' => 'name:Trvanie synchronizácie (sek.)|type:integer|min:0|required',
            ])->add('disabled'),
        ];
    }

    protected $settings = [
        'title.rows' => 'Posledné synchronizácie',
        'title.update' => 'Synchronizácia zo dňa :created',
        'columns.created.name' => 'Dátum vytvorenia',
        'columns.status.name' => 'Stav',
        'columns.status.encode' => false,
    ];

    public function setAdminAttributes($attributes)
    {
        $attributes['created'] = $this->created_at->format('d.m.Y H:i');
        $attributes['status'] = '<strong style="color: '.($this->errors ? 'red' : 'green').'">'.($this->errors ? 'Zlyhal' : 'V poriadku').'</strong>';

        return $attributes;
    }

    /**
     * Save all reports outputs. If debug is turned ON, exception will be thrown into console
     *
     * @param  array  $classes
     * @param  Illuminate\Console\Command  $command
     * @return  void
     */
    public function makeReport($name = '', $classes = null, $command = null)
    {
        $total = Admin::start();

        foreach ($classes as $namespace) {
            $synchronizer = is_object($namespace) ? $namespace : new $namespace;

            if ( !($synchronizer instanceof Synchronizer) ){
                throw new Exception('Injected class into synchronizer must by Synchronizer class.');
            }

            $synchronizer->tryOrLog($command);
        }

        $this->create([
            'name' => $name,
            'errors' => $this->toMessageFormat(SynchronizerFacade::getAllErrors()),
            'messages' => $this->toMessageFormat(SynchronizerFacade::getAllMessages()),
            'duration' => round(Admin::end($total)),
        ]);

        $this->removeOldReports($name);
    }

    /*
     * Add numbers into messages
     */
    private function toMessageFormat($lines)
    {
        return implode("\n", $lines);
    }

    /*
     * Remove older reports than x...
     */
    public function removeOldReports($name)
    {
        $removeOlderReportsFrom = $this
                                    ->where('name', $name)
                                    ->skip(env('ARCHIVE_REPORTS') ?: 4)
                                    ->take(1)
                                    ->select(['id'])
                                    ->first();

        if ( $removeOlderReportsFrom ) {
            $this->where('id', '<', $removeOlderReportsFrom->getKey())
                 ->where('name', $name)
                 ->delete();
        }
    }
}