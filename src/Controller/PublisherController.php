<?php

namespace App\Controller;

use App\Entity\Publisher;
use App\Entity\User;
use App\Form\PublisherType;
use App\Repository\PublisherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublisherController extends AbstractController
{
    private EntityManagerInterface $em;
    private PublisherRepository $publisherRepository;

    public function __construct(EntityManagerInterface $em, PublisherRepository $publisherRepository)
    {
        $this->em = $em;
        $this->publisherRepository = $publisherRepository;
    }

    final public function index(Request $request): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $publisher = new Publisher($user);
        $form = $this->createForm(PublisherType::class, $publisher);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($publisher);
            $this->em->flush();
        }

        return $this->render('publisher/index.html.twig', [
            'form' => $form->createView(),
            'publishers' => $this->getPublishers(),
        ]);
    }

    final public function edit(Publisher $publisher, Request $request): Response
    {
        $action = $this->generateUrl('publisher_edit', ['id' => $publisher->getId()]);

        $form = $this->createForm(PublisherType::class, $publisher, ['action' => $action]);
        $form->remove('url');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($publisher);
            $this->em->flush();

            return $this->redirect($this->generateUrl('publisher_index'));
        }

        return $this->render('publisher/_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function getPublishers(): array
    {
        $publishers = $this->publisherRepository->findAll();

        uasort(
            $publishers,
            static fn(Publisher $first, Publisher $second) => $second->getSortingScore() <=> $first->getSortingScore()
        );

        return $publishers;
    }
}
