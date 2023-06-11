<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Form\IngredientType;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ingredient', name: 'ingredient')]
class IngredientController extends AbstractController
{

    #[Route('/', name: '_index', methods:['GET'])]
    public function index(IngredientRepository $ingredientRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $ingredients = $paginator->paginate(
            $ingredientRepository->findAll(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('pages/ingredient/index.html.twig', [
            'ingredients' => $ingredients
        ]);
    }

    #[Route('/new', name:'_new', methods:['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ingredient = new Ingredient();
        $form = $this->createForm(IngredientType::class, $ingredient);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $ingredient = $form->getData();

            $em->persist($ingredient);
            $em->flush();

            $this->addFlash('success', 'Ingrediente criado com sucesso!');
            return $this->redirectToRoute('ingredient_index');
        }

        return $this->render('pages/ingredient/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edit/{id<\d+>}', name:'_edit', methods:['GET', 'POST'])]
    public function edit(Ingredient $ingredient, Request $request, EntityManagerInterface $em): Response {

        $form = $this->createForm(IngredientType::class, $ingredient);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $ingredient = $form->getData();

            $em->persist($ingredient);
            $em->flush();

            $this->addFlash('success', 'Ingrediente editado com sucesso!');
            return $this->redirectToRoute('ingredient_index');
        }

        return $this->render('pages/ingredient/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{id<\d+>}', name:'_delete', methods:['POST'])]
    public function delete(Request $request, Ingredient $ingredient, IngredientRepository $ingredientRepository): Response {

        if ($this->isCsrfTokenValid('delete'.$ingredient->getId(), $request->request->get('_token'))) {

            $ingredientRepository->remove($ingredient, true);
            $this->addFlash('success', 'Ingrediente excluido com sucesso!');
        }
        return $this->redirectToRoute('ingredient_index', [], Response::HTTP_SEE_OTHER);
    }
}
