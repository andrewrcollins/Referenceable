<?php

declare(strict_types=1);

namespace MoSaid\ModelReference\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferenceGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Model $model,
        public string $reference,
        public array $config
    ) {}
}