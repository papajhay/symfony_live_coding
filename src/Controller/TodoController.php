<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TodoController extends AbstractController
{
    #[Route('/', name: 'app_todo_index')]
    public function __invoke(): Response
    {
        return $this->render('todo/index.html.twig');
    }
}
