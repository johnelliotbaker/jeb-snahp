<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type as Type;
use \Symfony\Component\Form\FormBuilderInterface;
use jeb\snahp\Apps\RequestForm\Models\Ebook;

class EbookType extends AbstractType
{
    public static $alias = 'ebook';
    const CLASSNAME = Ebook::class;

    public function buildForm(FormBuilderInterface $builder, array $options)/*{{{*/
    {
        $builder
            ->add('filehost', FileHostType::class)
            ->add(
                'format',
                Type\ChoiceType::class,
                [
                    'attr' => ['tabindex' => 2],
                    'choices' => [
                        'PDF or EPUB' => 'PDF or EPUB',
                        'PDF' => 'PDF',
                        'EPUB' => 'EPUB',
                    ]
                ]
            )
            ->add(
                'language',
                Type\TextType::class,
                [
                    'attr' => ['tabindex' => 2],
                    'data' => 'English',
                ]
            )
            ->add(
                'requestType',
                Type\HiddenType::class,
                [ 'attr' => ['value' => self::$alias] ]
            );
    }/*}}}*/
}
