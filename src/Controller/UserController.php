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
// use Symfony\Component\Security\Http\Attribute\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/user', name: 'user')]
class UserController extends AbstractController
{
    #[Route('/edit/{id}', name: '_edit', methods:['GET', 'POST'])]
    #[Security("is_granted('ROLE_USER') and user === choosenUser")]
    public function edit(User $choosenUser, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserType::class, $choosenUser);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            if($hasher->isPasswordValid($choosenUser, $form->getData()->getPlainPassword())) {

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
    #[Security("is_granted('ROLE_USER') and user === choosenUser")]
    public function editPassword(User $choosenUser,Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserPasswordType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            if($hasher->isPasswordValid($choosenUser, $form->getData()['plainPassword'])) {

                $choosenUser->setUpdatedAt(new \DateTimeImmutable());
                $choosenUser->setPlainPassword($form->getData()['newPassword']);

                $em->persist($choosenUser);
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

