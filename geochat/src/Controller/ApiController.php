<?php

namespace App\Controller;

use App\Repository\MessageRepository;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

// ce controller montre le fichier json qui sera utilisé par la carte

#[Route('/api')]
class ApiController extends AbstractController
{
    #[View(serializerGroups: ['message_basic'])]
    #[Route('/message', name: 'app_api')]
    public function index(MessageRepository $messageRepository, Request $request)/*: Response*/
    {
        $messages = $messageRepository->findBy([], ['date' => 'DESC'], 10);

        $radius = $request->query->get("radius", 2);
        $address = $request->query->get("address");

        return [
            'messages' => $messages,
        ];
        /*
        $message = $messageRepository->findAll();
        $data = [];

        foreach($message as $m){
            $data[] = [
                'id' => $m->getId(),
                'longitude' => $m->getLongitude(),
                'latitude' => $m->getLatitude(),
                'text' => $m->getText(),
                'adress' => $m->getAdress(),
                'date' => $m->getDate(),
            ];
        }
        return $this->json($data);*/
    }
}
