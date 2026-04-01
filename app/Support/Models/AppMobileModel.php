<?php

namespace App\Support\Models;

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
        return 'app_mobile.'.$this->baseTable();
    }

    public static function tableName(): string
    {
        return (new static())->getTable();
    }
}
