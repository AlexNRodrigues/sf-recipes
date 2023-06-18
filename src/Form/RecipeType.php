<?php

namespace App\Form;

use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Repository\IngredientRepository;
use ContainerReeTa4x\getVichUploader_Form_Type_ImageService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Form\Type\VichImageType;

class RecipeType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'minlength' => '2',
                    'maxlength' => '50'
                ],
                'label' => 'Nome',
                'label_attr' => [
                    'class' => 'form-label mt-4'
                ],
                'constraints' => [
                    new Assert\Length(['min' => 2, 'max' => 50]),
                    new Assert\NotBlank()
                ]
            ])
            ->add('time', IntegerType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 1440
                ],
                'label' => 'Tempo (em minutos)',
                'label_attr' => [
                    'class' => 'form-label mt-4'
                ],
                'constraints' => [
                    new Assert\LessThan(1441),
                    new Assert\Positive()
                ],
                'required' => false
            ])
            ->add('nbPeople', IntegerType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 100
                ],
                'label' => 'Número de pessoas',
                'label_attr' => [
                    'class' => 'form-label mt-4'
                ],
                'constraints' => [
                    new Assert\LessThan(100),
                    new Assert\Positive()
                ],
                'required' => false
            ])
            ->add('difficulty', RangeType::class, [
                'attr' => [
                    'class' => 'form-range',
                    'min' => 1,
                    'max' => 6
                ],
                'label' => 'Dificuldade',
                'label_attr' => [
                    'class' => 'form-label mt-4'
                ],
                'constraints' => [
                    new Assert\LessThan(6),
                    new Assert\Positive()
                ]
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Descrição',
                'label_attr' => [
                    'class' => 'form-label mt-4'
                ],
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('price', TextType::class, [
                'attr' => [
                    'class' => 'form-control price',
                ],
                'label' => 'Preço',
                'label_attr' => [
                    'class' => 'form-label mt-4'
                ],
                'constraints' => [
                    new Assert\Positive(),
                ],
                'required' => false
            ])
            ->add('isFavorite', CheckboxType::class, [
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label' => 'Favorito ?',
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
                'constraints' => [
                ],
                'required' => false
            ])
            ->add('imageFile', VichImageType::class, [
                'delete_label' => 'Remove file',
                'label' => 'Foto da receita',
                'label_attr' => [
                    'class' => 'for-label mt-4'
                ],
                'required' => false
            ])
            ->add('ingredients', EntityType::class, [
                'class' => Ingredient::class,
                'query_builder' => function (IngredientRepository $ir) {
                    return $ir->createQueryBuilder('i')
                        ->where('i.user = :user')
                        ->orderBy('i.name', 'ASC')
                        ->setParameter('user', $this->security->getUser());
                },
                'choice_label' => 'name',
                'multiple' => true,
                'attr' => [
                    'class' => 'form-select select-multiple'
                ],
                'label' => 'Ingredientes',
                'label_attr' => [
                    'class' => 'form-label mt-4'
                ],
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-light mt-4',
                ],
                'label' => 'Criar Receita'
            ])
        ;

        $builder->get('price')
            ->addModelTransformer(new CallbackTransformer(
                function ($price) {
                    $price = $price / 100;

                    // para exibição do dado no input
                    return number_format($price, 2, ',', '.');
                },
                function ($price) {
                    $price = str_replace(['.', ','], ['', '.'], $price);
                    $price = $price * 100;
                    $price = (int)ceil($price);

                    // para o retorno ao controller
                    return $price;
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
