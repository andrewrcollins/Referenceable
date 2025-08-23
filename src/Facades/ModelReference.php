<?php

namespace MohamedSaid\Referenceable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MohamedSaid\Referenceable\ModelReference
 */
class ModelReference extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MohamedSaid\Referenceable\ModelReference::class;
    }
}
