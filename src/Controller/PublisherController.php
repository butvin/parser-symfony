<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Publisher;
use App\Form\PublisherType;
use App\Repository\PublisherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class PublisherController extends AbstractController
{
    private EntityManagerInterface $em;
    private PublisherRepository $repository;

    public function __construct(EntityManagerInterface $em, PublisherRepository $repository)
    {
        $this->em = $em;
        $this->repository = $repository;
    }

    public function index(Request $request): Response
    {
        $publisher = new Publisher($this->getUser());
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

    public function edit(Publisher $publisher, Request $request): Response
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
        $publishers = $this->repository->findAll();

        uasort(
            $publishers,
            static fn(Publisher $first, Publisher $second) =>
                $second->getSortingScore() <=> $first->getSortingScore()
        );

        return $publishers;
    }
}
