<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Services\AddressAPIService;
use PhpParser\Node\Expr\Cast\Array_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/message')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_message_index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository): Response
    {
        return $this->render('message/index.html.twig', [
            'messages' => $messageRepository->findAll(),
        ]);
    }

    #[Route('/map', name: 'app_message_map', methods: ['GET'])]
    public function map(MessageRepository $messageRepository): Response
    {
        return $this->render('message/map.html.twig', [
            'messages' => $messageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_message_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MessageRepository $messageRepository): Response
    {
        $api = new AddressAPIService();
        $message = new Message();

        /*$message
            ->setLongitude(44.8437806)
            ->setLatitude(-0.5710693);*/

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // appeler notre méthode pour avoir notre longitude et latitude
            $rep = $api->getLngLat($message->getAdress());

            if ($rep != null) {
                $message
                    ->setLongitude($rep['longitude'])
                    ->setLatitude($rep['latitude']);
            } else {
                $message
                    ->setLongitude(0)
                    ->setLatitude(0);
            }

            $messageRepository->save($message, true);

            return $this->redirectToRoute('app_message_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('message/new.html.twig', [
            'message' => $message,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_message_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Message $message, MessageRepository $messageRepository, AddressAPIService $api): Response
    {
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageRepository->save($message, true);

            return $this->redirectToRoute('app_message_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('message/edit.html.twig', [
            'message' => $message,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_message_delete', methods: ['POST'])]
    public function delete(Request $request, Message $message, MessageRepository $messageRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $message->getId(), $request->request->get('_token'))) {
            $messageRepository->remove($message, true);
        }

        return $this->redirectToRoute('app_message_index', [], Response::HTTP_SEE_OTHER);
    }
}
