<?php

namespace App\Twig\Components;

use App\Entity\Todo;
use App\Form\CreateTodoType;
use App\Form\Model\CreateTodoInput;
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
final class CreateTodo
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public int $listVersion = 0;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[LiveAction]
    public function createTodo(): void
    {
        $this->submitForm();

        /** @var CreateTodoInput $data */
        $data = $this->getForm()->getData();

        $title = trim($data->title);
        $description = $data->description !== null ? trim($data->description) : null;

        $todo = new Todo($title, $description !== '' ? $description : null);
        $this->entityManager->persist($todo);
        $this->entityManager->flush();

        ++$this->listVersion;
        $this->emitUp('todo:created');
        $this->resetForm();
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(CreateTodoType::class, new CreateTodoInput());
    }
}

