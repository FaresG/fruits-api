<?php

namespace App\Controller;

use App\Data\FilterData;
use App\Entity\Fruit;
use App\Form\FilterFormType;
use App\Form\FruitType;
use App\Repository\FruitRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;

class FruitController extends AbstractController
{
    public function __construct(HttpClientInterface $client, SerializerInterface $serializer, FruitRepository $fruitRepository)
    {
        $this->fruitRepository = $fruitRepository;
        $this->client = $client;
        $this->serializer = $serializer;
    }

    #[Route('/', name: 'app_fruit_index')]
    public function index(Request $request): Response
    {
        $data = new FilterData();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(FilterFormType::class, $data);
        $form->handleRequest($request);
        $fruits = $this->fruitRepository->findFilter($data);
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

        $this->addFlash('success', 'Fetched Fruits are successfully added!');

        return $this->redirectToRoute('app_fruit_index');
    }

    #[Route('/fruit/new', name: 'app_fruit_new')]
    public function new(Request $request, MailerInterface $mailer) : Response
    {
        $fruit = new Fruit();
        $form = $this->createForm(FruitType::class, $fruit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $fruit = $form->getData();
            $this->fruitRepository->save($fruit, true);

            $this->addFlash('success', 'New fruit added!');

            // send notification email
            $email = (new Email())
                ->from('notifications@fruits.com')
                ->to('admin@fruits.com')
                ->priority(Email::PRIORITY_HIGH)
                ->subject('Notification')
                ->text('The fruit has been added!');
            $mailer->send($email);
        }

        return $this->renderForm('fruit/new.html.twig', [
            'form' => $form,
        ]);
    }
}
