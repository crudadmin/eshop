<?php

namespace AdminEshop\Contracts\Synchronizer\Concerns;

use Admin\Core\Contracts\DataStore;

class SynchronizerLogger
{
    use DataStore;

    /*
     * We need store all errors and messages under this key to have them globbaly on one place
     *
     * @Admin\Core\Contracts\DataStore;
     */
    public function getStoreKey()
    {
        return 'store.synchronizer.logger';
    }

    /*
     * Synchronizer messages
     */
    protected $messages = [];

    /*
     * Synchronizer errors
     */
    protected $errors = [];

    /*
     * Only info message, won't be saved int olog
     */
    public function info($message)
    {
        return $this->message($message, false);
    }

    /*
     * Log message
     */
    public function message($message, $saveIntoReport = true)
    {
        if ( $saveIntoReport === true ) {
            $this->push('messages', $message);

            $this->messages[] = $message;
        }

        if ( $command = $this->getCommand() ) {
            $command->line($message);
        }
    }

    /*
     * Insert input into console, and does not save it into report
     */
    public function consoleMessage($message)
    {
        return $this->message($message, false);
    }

    /*
     * Log errror
     */
    public function error($message, $prefix = '')
    {
        $this->push('errors', $message);

        $this->errors[] = $message;

        if ( $command = $this->getCommand() ) {
            $command->error($prefix.$message);
        }
    }

    /*
     * Return messages of given service
     */
    public function getMessages()
    {
        return $this->messages ?: [];
    }

    /*
     * Return errors of given server
     */
    public function getErrors()
    {
        return $this->errors ?: [];
    }

    /*
     * Returns all messages from all sub services which uses this logger
     */
    public function getAllMessages()
    {
        return $this->get('messages') ?: [];
    }

    /*
     * Returns all errors from all sub services which uses this logger
     */
    public function getAllErrors()
    {
        return $this->get('errors') ?: [];
    }
}