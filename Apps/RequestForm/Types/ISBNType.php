<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use \Symfony\Component\Form\CallbackTransformer;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Form\Extension\Core\Type as Type;
use \Symfony\Component\Validator\Constraints as Assert;

class ISBNType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)/*{{{*/
    {
        $builder
            ->add(
                'isbn',
                Type\TextType::class,
                [
                    'attr' => [
                        'tabindex' => 2,
                        'class' => 'inputbox autowidth',
                        'size' => 30
                    ],
                    'constraints' => new Assert\Isbn(),
                    'label' => 'ISBN',
                    'required' => false,
                ]
            );

        $builder
            ->get('isbn')
            ->addModelTransformer(
                new CallbackTransformer(
                    function ($isbnString) {
                        return $isbnString ? $isbnString : null;
                    },
                    function ($isbnString) {
                        $isbnString = preg_replace('#-#', '', $isbnString);
                        return $isbnString ? $isbnString : null;
                    }
                )
            );
        ;
    }/*}}}*/
}
