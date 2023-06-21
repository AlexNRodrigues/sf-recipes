<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/contact', name: 'contact')]
class ContactController extends AbstractController
{
    #[Route('/', name: '_index', methods:['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $contact = new Contact();

        // dd($this->getUser());

        if($this->getUser()) {
            $contact->setFullName($this->getUser()->getFullName());
            $contact->setEmail($this->getUser()->getEmail());
        }

        $formContact = $this->createForm(ContactType::class, $contact);
        $formContact->handleRequest($request);

        if($formContact->isSubmitted() && $formContact->isValid()) {
            $contact = $formContact->getData();

            $em->persist($contact);
            $em->flush();

            $this->addFlash('success', 'Mensagem de contato enviada com sucesso!');
            return $this->redirectToRoute('contact_index');
        }

        return $this->render('pages/contact/index.html.twig', [
            'formContact' => $formContact->createView(),
        ]);
    }
}
