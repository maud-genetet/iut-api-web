<?php



namespace App\Controller;


use App\Entity\Message;
use App\Repository\MessageRepository;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Services\AddressAPIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use OpenApi\Attributes as OA;


// ce controller montre le fichier json qui sera utilisé par la carte

// pas besoin de #[View()] car on ne renvoie pas de vue


#[Route('/api')]
class ApiController extends AbstractController
{

    #[OA\Get(
        tags: "messages",
        summary: "Get messages",
        description: "Get messages 2",
    )]
    #[OA\Parameter(
        name: "adress",
        description: "coucou",
        example: "salut",
        in: "query",
        required: true,
    )]
    #[OA\Parameter(
        name: "radius",
        in: "query",
        required: false
    )]
    #[OA\Parameter(
        name: "posted_after",
        in: "query",
        required: false
    )]
    #[OA\Response(
        response: 200,
        description: "successful operation",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(
                ref: "#/components/schemas/Message"
            )
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Problem with the request"
    )]
    #[View(serializerGroups: ['message_basic'])]
    #[Route('/messages', name: 'app_api', methods: ['GET'])]
    public function index(MessageRepository $messageRepository, Request $request)/*: Response*/
    {
        // Récupération des paramètres en get on utilise pas $_GET car on est en symfony
        $address = $request->query->get('address');
        // si on a pas d'adresse il y a null dans $address
        // aller dans le profiler avec /_profiler/api/message... pour voir les dumps
        dump($address);

        if ($address == null) {
            return $this->json(['error' => 'The address parameter is required.'], Response::HTTP_BAD_REQUEST);
        }

        // 2000 car en mètres
        $radius = $request->query->get('radius', 2000);

        $posted_after = $request->query->get('posted_after');


        // Récupération des coordonnées GPS
        $addressAPI = new AddressAPIService();

        $lnglat = $addressAPI->getLngLat($address);

        if ($lnglat == null) {
            return $this->json(['error' => 'The address is not valid.'], Response::HTTP_BAD_REQUEST);
        }

        $longitude = $lnglat["longitude"];
        $latitude = $lnglat["latitude"];

        $queryBuiler = $messageRepository->findClose($longitude, $latitude, $radius)
            ->orderBy('m.date', 'DESC')
            ->setMaxResults(10);

        if ($posted_after != null) {
            $queryBuiler->andWhere('m.date > :posted_after')
                ->setParameter('posted_after', new \DateTime($posted_after));
        }


        $message = $queryBuiler->getQuery()->execute();

        return ["messages" => ["message" => $message]];

        /*
        $longitude = $lnglat["longitude"];
        $latitude = $lnglat["latitude"];

        // requête pour récupérer les messages que l'on peut modifier
        $query = $messageRepository->findClose($longitude, $latitude, $radius)
            ->orderBy('m.date', 'DESC')
            ->setMaxResults(10);
*/

        // Récupération des messages
        //$message = $query->getQuery();


        //return ["messages" => ["message" => $message->execute()]];
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


    #[OA\RequestBody(
        required : true,
        description: "fichier jason",
        content : new OA\JsonContent(
            type : "array",
            items: new OA\Items(
                ref: "#/components/schemas/address"
            )
        )
    )]
    #[OA\Response(
        response : 200,
        description : "successful operation"
    )]
    #[OA\Response(
        response : 400,
        description : "Problem with the request"
    )]
    #[View()]
    #[Route('/message', methods: ['POST'])]
    public function addMessageEnJson(
        MessageRepository $messageRepository,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ) {
        // request->getContent() récupère le contenu de la requête ( le json )
        // $serializer->deserialize() permet de transformer le json en objet
        // bien verifier que message est bien une entité
        /*
        dump($request->getContent("text"));
        dump($request->getContent("adress"));
        if ($request->getContent("text") == null && $request->getContent("adress") == null) {
            return $this->json(['error' => 'The text parameter is required.'], Response::HTTP_BAD_REQUEST);
            dump("error");
        } else {
            $message = $serializer->deserialize($request->getContent(), Message::class, 'json');
            dump($request->getContent());
            dump($message);
        }*/
        $message = $serializer->deserialize($request->getContent(), Message::class, 'json');

        $error = $validator->validate($message);

        if (count($error) > 0) {
            return $this->json($error, Response::HTTP_BAD_REQUEST);
        }
        //$messageRepository->save($message, true); // true pour dire qu'on veut flusher
        $addressAPI = new AddressAPIService();

        $lnglat = $addressAPI->getLngLat($message->getAdress());
        $message->setLongitude($lnglat["longitude"]);
        $message->setLatitude($lnglat["latitude"]);



        $messageRepository->save($message, true);
        $em->flush();

        return ["message" => $message];
    }
}
