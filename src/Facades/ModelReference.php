<?php

namespace MoSaid\ModelReference\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MoSaid\ModelReference\ModelReference
 */
class ModelReference extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MoSaid\ModelReference\ModelReference::class;
    }
}
