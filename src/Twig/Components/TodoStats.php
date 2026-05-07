<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class TodoStats
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $totalTodos = 0;

    #[LiveProp]
    public int $remaining = 0;

    #[LiveProp]
    public int $completedCount = 0;
}

