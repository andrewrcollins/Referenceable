<?php

declare(strict_types=1);

namespace MoSaid\ModelReference\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ReferenceGeneratorInterface
{
    public function generate(Model $model, array $config = []): string;

    public function validate(string $reference, array $config = []): bool;

    public function supports(string $strategy): bool;
}