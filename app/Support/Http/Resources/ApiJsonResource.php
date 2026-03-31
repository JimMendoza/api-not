<?php

namespace App\Support\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiJsonResource extends JsonResource
{
    public static $wrap = null;
}