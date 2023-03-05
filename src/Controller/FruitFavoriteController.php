<?php

namespace App\Controller;

use App\Repository\FruitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FruitFavoriteController extends AbstractController
{
    public function __construct(FruitRepository $fruitRepository)
    {
        $this->fruitRepository = $fruitRepository;
    }

    #[Route('/fruit/favorites', name: 'app_fruit_favorite')]
    public function index(Request $request) : Response
    {
        $fruits = $this->fruitRepository->findFavorite($request->query->getInt('page', 1));

        return $this->render('fruit_favorite/index.html.twig',[
            'fruits' => $fruits
        ]);
    }

    #[Route('/fruit/favorites/add/{id}', name: 'app_fruit_favorite_add')]
    public function add(int $id) : JsonResponse
    {
        $fruit = $this->fruitRepository->find($id);

        if (!$fruit)
        {
            return $this->json('Fruit not found', 404);
        }

        $fruit->setFavorite(!$fruit->isFavorite());
        $this->fruitRepository->save($fruit, true);

        return $this->json('Fruit updated successfully');
    }
}
