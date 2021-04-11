<?php

namespace AdminEshop\Contracts\Synchronizer\Imports;

use AdminEshop\Contracts\Synchronizer\Synchronizer;
use AdminEshop\Contracts\Synchronizer\SynchronizerInterface;
use Admin;

class CategoriesImport extends Synchronizer implements SynchronizerInterface
{
    public function getCategoryIdentifier()
    {
        return 'code';
    }

    public function handle(array $rows = null)
    {
        $this->synchronize(
            Admin::getModel('Category'),
            $this->getCategoryIdentifier(),
            $rows
        );
    }
}