<?php

namespace App\Twig\Components;

use App\Form\EditTodoType;
use App\Form\Model\EditTodoInput;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class EditTodo
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public int $todoId;

    #[LiveProp(writable: true)]
    public int $listVersion = 0;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly TodoRepository $todoRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[LiveAction]
    public function updateTodo(): void
    {
        $todo = $this->todoRepository->find($this->todoId);
        if (null === $todo) {
            return;
        }

        $this->submitForm();

        /** @var EditTodoInput $data */
        $data = $this->getForm()->getData();

        $title = trim($data->title);
        $description = $data->description !== null ? trim($data->description) : null;

        $todo->setTitle($title);
        $todo->setDescription($description !== '' ? $description : null);
        $this->entityManager->flush();

        ++$this->listVersion;
        $this->emitUp('todo:updated');
    }

    protected function instantiateForm(): FormInterface
    {
        $todo = $this->todoRepository->find($this->todoId);

        $data = new EditTodoInput();
        if (null !== $todo) {
            $data->title = $todo->getTitle();
            $data->description = $todo->getDescription();
        }

        return $this->formFactory->create(EditTodoType::class, $data);
    }
}

