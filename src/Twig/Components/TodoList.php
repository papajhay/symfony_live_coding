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

    #[LiveProp(writable: true)]
    public string $searchQuery = '';

    /**
     * @var list<int>
     */
    #[LiveProp(writable: true)]
    public array $filteredTodos = [];

    #[LiveProp]
    public int $page = 1;

    #[LiveProp]
    public int $currentPage = 1;

    public int $todosPerPage = 7;

    #[LiveProp]
    public int $totalPages = 1;

    public function __construct(
        private readonly TodoRepository $todoRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function mount(array $data = []): void
    {
        $this->currentPage = max(1, (int) ($data['currentPage'] ?? $data['page'] ?? 1));
        $this->totalPages = max(1, (int) ($data['totalPages'] ?? 1));
    }

    /**
     * @return list<Todo>
     */
    public function getTodos(): array
    {
        $todos = $this->applySearch($this->getAllTodos());
        $this->filteredTodos = array_map(
            static fn (Todo $todo): int => $todo->getId(),
            $todos
        );
        $this->refreshPaginationMeta(\count($todos));

        return $todos;
    }

    /**
     * @return list<Todo>
     */
    public function getPaginatedTodos(): array
    {
        $todos = $this->getTodos();
        $offset = ($this->currentPage - 1) * $this->todosPerPage;
        $paginated = \array_slice($todos, $offset, $this->todosPerPage);

        return $paginated;
    }

    public function getRemainingCount(): int
    {
        return \count(array_filter(
            $this->getTodos(),
            static fn (Todo $todo): bool => !$todo->isDone()
        ));
    }

    public function getTotalTodos(): int
    {
        return \count($this->getTodos());
    }

    public function getCompletedCount(): int
    {
        return \count(array_filter(
            $this->getTodos(),
            static fn (Todo $todo): bool => $todo->isDone()
        ));
    }

    #[LiveAction]
    public function searchTodos(): void
    {
        $this->currentPage = 1;
        ++$this->listVersion;
    }

    #[LiveAction]
    public function clearSearch(): void
    {
        $this->searchQuery = '';
        $this->filteredTodos = [];
        $this->currentPage = 1;
        ++$this->listVersion;
    }

    #[LiveAction]
    public function toggleTodo(#[LiveArg] Todo $todo): void
    {
        $todo->setDone(!$todo->isDone());
        $this->entityManager->flush();
        ++$this->listVersion;
    }

    #[LiveAction]
    public function deleteTodo(#[LiveArg] Todo $todo): void
    {
        $this->entityManager->remove($todo);
        $this->entityManager->flush();

        if ($this->editingTodoId === $todo->getId()) {
            $this->editingTodoId = null;
        }

        $this->refreshPaginationMeta($this->getTotalTodos());
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

    /**
     * @return list<Todo>
     */
    private function getAllTodos(): array
    {
        return $this->todoRepository->findAllOrdered();
    }

    /**
     * @param list<Todo> $todos
     *
     * @return list<Todo>
     */
    private function applySearch(array $todos): array
    {
        $query = mb_strtolower(trim($this->searchQuery));
        if ($query == '') {
            return $todos;
        }

        return array_values(array_filter(
            $todos,
            static fn (Todo $todo): bool => str_contains(mb_strtolower($todo->getTitle()), $query)
        ));
    }

    private function refreshPaginationMeta(int $totalTodos): void
    {
        $perPage = max(1, $this->todosPerPage);
        $this->todosPerPage = $perPage;
        $this->totalPages = max(1, (int) ceil($totalTodos / $perPage));
        $this->currentPage = max(1, min($this->currentPage, $this->totalPages));
    }
}
