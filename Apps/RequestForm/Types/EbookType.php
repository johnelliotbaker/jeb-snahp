<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type as Type;
use \Symfony\Component\Form\FormBuilderInterface;
use jeb\snahp\Apps\RequestForm\Models\Ebook;

use \Symfony\Component\Validator\Constraints as Assert;

class EbookType extends AbstractType
{
    public static $alias = 'ebook';
    const CLASSNAME = Ebook::class;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('filehost', FileHostType::class)
            ->add(
                'format',
                Type\ChoiceType::class,
                [
                    'attr' => ['tabindex' => 2],
                    'choices' => [
                        'EPUB or PDF or MOBI' => 'EPUB or PDF or MOBI',
                        'EPUB' => 'EPUB',
                        'PDF' => 'PDF',
                        'MOBI' => 'MOBI',
                    ]
                ]
            )
            ->add(
                'authors',
                Type\TextType::class,
                [
                    'attr' => [
                        'tabindex' => 2,
                        'class' => 'inputbox autowidth',
                        'size' => 30
                    ],
                    'required' => false,
                ]
            )
            ->add('isbn', ISBNType::class)
            ->add(
                'edition',
                Type\TextType::class,
                [
                    'attr' => [
                        'tabindex' => 2,
                        'class' => 'inputbox autowidth',
                        'size' => 30
                    ],
                    'required' => false,
                    'help' => 'US version, 3rd edition'
                ]
            )
            ->add(
                'language',
                Type\TextType::class,
                [
                    'attr' => [
                        'tabindex' => 2,
                        'class' => 'inputbox autowidth',
                        'size' => 30
                    ],
                    'data' => 'English',
                ]
            )
            ->add(
                'link',
                Type\TextType::class,
                [
                    'attr' => [
                        'tabindex' => 2,
                        'class' => 'inputbox autowidth',
                        'size' => 45
                    ],
                    'required' => false,
                ]
            )
            ->add(
                'requestType',
                Type\HiddenType::class,
                [ 'attr' => ['value' => self::$alias] ]
            );
    }
}
