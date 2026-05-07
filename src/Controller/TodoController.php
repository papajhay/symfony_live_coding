<?php

namespace App\Controller;

use App\Repository\TodoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TodoController extends AbstractController
{
    #[Route('/todos/{page<\d+>?1}', name: 'todo_path')]
    public function __invoke(int $page, TodoRepository $todoRepository): Response
    {
        $allTodos = $todoRepository->findAllOrdered();
        $todosPerPage = 7;
        $totalTodos = \count($allTodos);
        $totalPages = max(1, (int) ceil($totalTodos / $todosPerPage));
        $currentPage = max(1, min($page, $totalPages));
        $paginatedTodos = \array_slice($allTodos, ($currentPage - 1) * $todosPerPage, $todosPerPage);

        return $this->render('todo/index.html.twig', [
            'page' => $currentPage,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'paginatedCount' => \count($paginatedTodos),
        ]);
    }
}
