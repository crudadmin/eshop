<?php

namespace AdminEshop\Contracts\Synchronizer;

use AdminEshop\Contracts\Synchronizer\Concerns\HasCasts;
use AdminEshop\Contracts\Synchronizer\Concerns\HasGlobalCasts;
use AdminEshop\Contracts\Synchronizer\Concerns\HasImporter;
use AdminEshop\Contracts\Synchronizer\Concerns\HasLogger;
use AdminEshop\Contracts\Synchronizer\Concerns\SynchronizerLogger;
use AdminEshop\Contracts\Synchronizer\ImportTemplate;
use Throwable;

class Synchronizer extends SynchronizerLogger
{
    use HasImporter,
        HasCasts,
        HasGlobalCasts;

    /*
     * Command for messages output
     */
    protected $command = null;

    protected $template = null;

    public function setTemplate(ImportTemplate $template = null)
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    /*
     * Get command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /*
     * Set comand output
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    public function tryOrLog($command = null)
    {
        if ( $command ) {
            $this->setCommand($command);
        }

        $preparedData = $this->prepare();

        try {
            $this->message('************* '.class_basename(get_class($this)));

            $this->handle($preparedData);
        } catch(Throwable $e){
            $this->error($e->getMessage().' in '.str_replace(base_path(), '', $e->getFile()).':'.$e->getLine());

            //If debug is turned on
            if ( env('APP_DEBUG') === true ){
                throw $e;
            }
        }
    }
}