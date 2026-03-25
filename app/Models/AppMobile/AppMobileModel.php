<?php

namespace App\Models\AppMobile;

use Illuminate\Database\Eloquent\Model;

abstract class AppMobileModel extends Model
{
    abstract protected function baseTable(): string;

    public function getConnectionName()
    {
        return (string) config('mobile.connection', parent::getConnectionName() ?: config('database.default'));
    }

    public function getTable()
    {
        $baseTable = $this->baseTable();

        if ($this->getConnection()->getDriverName() === 'pgsql') {
            return 'app_mobile.'.$baseTable;
        }

        return 'app_mobile_'.$baseTable;
    }

    public static function tableName(): string
    {
        return (new static())->getTable();
    }
}
