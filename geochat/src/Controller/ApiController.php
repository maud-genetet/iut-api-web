<?php

namespace App\Controller;

use App\Repository\MessageRepository;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Services\AddressAPIService;
use FOS\RestBundle\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

// ce controller montre le fichier json qui sera utilisé par la carte

#[Route('/api')]
class ApiController extends AbstractController
{
    #[View(serializerGroups: ['message_basic'])]
    #[Route('/message', name: 'app_api')]
    public function index(MessageRepository $messageRepository, Request $request)/*: Response*/
    {
        // Récupération des paramètres
        
        $address = $request->query->get('address');
        
        if (!$address) {
            return $this->json(['error' => 'The address parameter is required.'], Response::HTTP_BAD_REQUEST);
        }

        // 2000 car en mètres
        $radius = $request->query->get('radius', 2000);


        // Récupération des coordonnées GPS
        $addressAPI = new AddressAPIService();
 
        $lnglat = $addressAPI->getLngLat($address);

        $longitude = $lnglat["longitude"];
        $latitude = $lnglat["latitude"];

        // Recherche des adrresses à proximité
        $query = $messageRepository->findClose($longitude, $latitude, $radius)
            ->orderBy('m.date', 'DESC')
            ->setMaxResults(10);


        $message = $query->getQuery();

        return ["messages" => ["message" => $message->execute()]];
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

    #[View(serializerGroups: ['message_basic'])]
    #[Route('/message', methods: ['POST'])]
    public function addMessageEnJson(Request $request, SerializerInterface $serializer)
    {
        $message = $serializer->deserialize($request->getContent(), Message::class, 'json');
        return 0;
    }
} 
