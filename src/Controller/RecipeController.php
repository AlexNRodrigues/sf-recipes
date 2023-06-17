<?php

namespace App\Controller;

use App\Entity\Mark;
use App\Entity\Recipe;
use App\Form\MarkType;
use App\Form\RecipeType;
use App\Repository\MarkRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// use Symfony\Component\Security\Http\Attribute\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/recipe', name: 'recipe')]
#[IsGranted('ROLE_USER')]
class RecipeController extends AbstractController
{
    #[Route('/', name: '_index', methods:['GET'])]
    public function index(RecipeRepository $recipeRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $recipes = $paginator->paginate(
            $recipeRepository->findBy(['user' => $this->getUser()]),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('pages/recipe/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/show/{id}', name:'_show', methods:['GET', 'POST'])]
    #[Security("is_granted('ROLE_USER') and recipe.isIsPublic() === true")]
    public function show(Recipe $recipe, Request $request, MarkRepository $markRepository, EntityManagerInterface $em): Response
    {
        $mark = new Mark();

        $form = $this->createForm(MarkType::class, $mark);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $mark->setUser($this->getUser())
                ->setRecipe($recipe);

            $existingMark = $markRepository->findOneBy([
                'user' => $this->getUser(),
                'recipe' => $recipe
            ]);

            if(!$existingMark) {
                $em->persist($mark);
            } else {
                $existingMark->setMark(
                    $form->getData()->getMark()
                );
            }

            $em->flush();

            $this->addFlash('success', 'Nota adicionada com sucesso!');
            return $this->redirectToRoute('recipe_show', ['id' => $recipe->getId()]);
        }

        return $this->render('pages/recipe/show.html.twig', [
            'recipe' => $recipe,
            'form' => $form->createView()
        ]);
    }

    #[Route('/publish', name:'_index_public', methods:['GET'])]
    public function indexPublic(RecipeRepository $recipeRepository): Response
    {

        return $this->render('pages/recipe/index_public.html.twig', [
            'recipes' => $recipeRepository->findPublicRecipe(100),
        ]);
    }

    #[Route('/new', name:'_new', methods:['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $recipe = $form->getData();
            $recipe->setUser($this->getUser());

            $em->persist($recipe);
            $em->flush();

            $this->addFlash('success', 'receita criado com sucesso!');
            return $this->redirectToRoute('recipe_index');
        }

        return $this->render('pages/recipe/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edit/{id<\d+>}', name:'_edit', methods:['GET', 'POST'])]
    #[Security("is_granted('ROLE_USER') and user === recipe.getUser()")]
    public function edit(Recipe $recipe, Request $request, EntityManagerInterface $em): Response {

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $recipe = $form->getData();

            $em->persist($recipe);
            $em->flush();

            $this->addFlash('success', 'Receita editada com sucesso!');
            return $this->redirectToRoute('recipe_index');
        }

        return $this->render('pages/recipe/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{id<\d+>}', name:'_delete', methods:['POST'])]
    #[Security("is_granted('ROLE_USER') and user === recipe.getUser()")]
    public function delete(Request $request, Recipe $recipe, RecipeRepository $recipeRepository): Response {

        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->request->get('_token'))) {

            $recipeRepository->remove($recipe, true);
            $this->addFlash('success', 'Receita excluida com sucesso!');
        }
        return $this->redirectToRoute('recipe_index', [], Response::HTTP_SEE_OTHER);
    }
}
