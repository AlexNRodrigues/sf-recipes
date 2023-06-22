<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3Validator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/contact', name: 'contact')]
class ContactController extends AbstractController
{
    #[Route('/', name: '_index', methods:['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em, MailService $mailService, Recaptcha3Validator $recaptcha3Validator): Response
    {
        $contact = new Contact();

        if($this->getUser()) {
            $contact->setFullName($this->getUser()->getFullName());
            $contact->setEmail($this->getUser()->getEmail());
        }

        $formContact = $this->createForm(ContactType::class, $contact);
        $formContact->handleRequest($request);

        if($formContact->isSubmitted() && $formContact->isValid()) {
            $contact = $formContact->getData();

            $score = $recaptcha3Validator->getLastResponse()->getScore();

            $em->persist($contact);
            $em->flush();

            // email
            $mailService->sendEmail(
                $contact->getEmail(),
                $contact->getSubject(),
                'emails/contact.html.twig',
                [
                    'contact' => $contact,
                    'score' => $score
                ]
            );

            $this->addFlash('success', 'Mensagem de contato enviada com sucesso!');
            return $this->redirectToRoute('contact_index');
        }

        return $this->render('pages/contact/index.html.twig', [
            'formContact' => $formContact->createView(),
        ]);
    }
}
