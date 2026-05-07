<?php

namespace App\Twig\Components;

use App\Entity\Todo;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class TodoList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $listVersion = 0;

    #[LiveProp(writable: true)]
    public ?int $editingTodoId = null;

    public function __construct(
        private readonly TodoRepository $todoRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<Todo>
     */
    public function getTodos(): array
    {
        return $this->todoRepository->findAllOrdered();
    }

    public function getRemainingCount(): int
    {
        return $this->todoRepository->countOpen();
    }

    public function getCompletedCount(): int
    {
        return $this->todoRepository->countCompleted();
    }

    #[LiveAction]
    public function toggleTodo(#[LiveArg] Todo $todo): void
    {
        $todo->setDone(!$todo->isDone());
        $this->entityManager->flush();
    }

    #[LiveAction]
    public function deleteTodo(#[LiveArg] Todo $todo): void
    {
        $this->entityManager->remove($todo);
        $this->entityManager->flush();

        if ($this->editingTodoId === $todo->getId()) {
            $this->editingTodoId = null;
        }
    }

    #[LiveAction]
    public function startEdit(#[LiveArg] Todo $todo): void
    {
        $this->editingTodoId = $todo->getId();
    }

    #[LiveAction]
    public function cancelEdit(): void
    {
        $this->editingTodoId = null;
    }

    #[LiveListener('todo:created')]
    public function onTodoCreated(): void
    {
        ++$this->listVersion;
    }

    #[LiveListener('todo:updated')]
    public function onTodoUpdated(): void
    {
        ++$this->listVersion;
        $this->editingTodoId = null;
    }
}
