<?php

namespace AdminEshop\Models\Orders;

use AdminPayments\Models\Payments\PaymentsLog;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class OrdersLog extends PaymentsLog
{
    protected $migration_date = '2020-03-27 06:49:16';
}