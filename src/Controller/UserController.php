<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserPasswordType;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user', name: 'user')]
class UserController extends AbstractController
{
    #[Route('/edit/{id}', name: '_edit', methods:['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('security_login');
        }

        if($this->getUser() !== $user) {
            return $this->redirectToRoute('home_index');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            if($hasher->isPasswordValid($user, $form->getData()->getPlainPassword())) {

                $user = $form->getData();

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Edição feita com sucessso!');
                return $this->redirectToRoute('home_index');
            }

            $this->addFlash('warning', 'A senha não são iguais!');
        }

        return $this->render('pages/user/edit.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/edit/change-password/{id}', name: '_change_password', methods:['GET', 'POST'])]
    public function editPassword(User $user,Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserPasswordType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            if($hasher->isPasswordValid($user, $form->getData()['plainPassword'])) {

                $user->setUpdatedAt(new \DateTimeImmutable());
                $user->setPlainPassword($form->getData()['newPassword']);

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Senha alterada com sucesso');
                return $this->redirectToRoute('home_index');
            }
            $this->addFlash('warning', 'Senha inserida não é valida');
            return $this->redirectToRoute('home_index');
        }

        return $this->render('pages/user/edit_password.html.twig', [
            'form' => $form
        ]);
    }
}

