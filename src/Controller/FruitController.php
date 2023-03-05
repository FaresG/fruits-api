<?php

namespace App\Controller;

use App\Data\FilterData;
use App\Entity\Fruit;
use App\Form\FilterFormType;
use App\Form\FruitType;
use App\Repository\FruitRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;

class FruitController extends AbstractController
{
    private FruitRepository $fruitRepository;

    public function __construct(HttpClientInterface $client, SerializerInterface $serializer, FruitRepository $fruitRepository)
    {
        $this->client = $client;
        $this->serializer = $serializer;
        $this->fruitRepository = $fruitRepository;
    }

    #[Route('/', name: 'app_fruit_index')]
    public function index(Request $request, FruitRepository $fruitRepository): Response
    {
        $data = new FilterData();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(FilterFormType::class, $data);
        $form->handleRequest($request);
        $fruits = $fruitRepository->findFilter($data);
        return $this->render('fruit/index.html.twig',[
            'fruits' => $fruits,
            'form' => $form->createView()
        ]);
    }

    #[Route('/fruit/fetch', name: 'app_fruit_fetch')]
    public function fetch() : Response
    {

        $response = $this->client->request(
            'GET',
            'https://fruityvice.com/api/fruit/all'
        );

        // deserialize objects and store to db
        foreach($response->toArray() as $fruit)
        {
            $fruit = $this->serializer->deserialize(json_encode($fruit), Fruit::class, 'json');
            $this->fruitRepository->save($fruit, true);
        }
        return new Response('ok', 201);
    }

    #[Route('/fruit/new', name: 'app_fruit_new')]
    public function new(Request $request) : Response
    {
        $fruit = new Fruit;
        $form = $this->createForm(FruitType::class, $fruit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $fruit = $form->getData();
            $this->fruitRepository->save($fruit, true);

            $this->addFlash('success', 'New fruit added!');
        }
        return $this->renderForm('fruit/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/fruit/favorites', name: 'app_fruit_favorites')]
    public function favorite() : Response
    {
        return $this->render('fruit/favorites.html.twig',[
        ]);
    }
}