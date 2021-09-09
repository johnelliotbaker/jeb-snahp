<?php
namespace jeb\snahp\Apps\RequestForm\Types;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type\HiddenType;
use \Symfony\Component\Form\Extension\Core\Type as Type;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints as Assert;
use jeb\snahp\Apps\RequestForm\Models\Anime;

class AnimeType extends AbstractType
{
    public static $alias = 'anime';
    const CLASSNAME = Anime::class;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $js = '
<script>
const subject = $("#subject");
const malLink = $("#mal-link");
subject.keyup((e) => {
    const request = subject.val();
    malLink.attr("href", "https://myanimelist.net/search/all?cat=all&q=" + request);
});
</script>';

        $builder
            ->add('filehost', FileHostType::class)
            ->add('videoResolution', VideoResolutionType::class)
            ->add('videoCodec', VideoCodecType::class)
            ->add(
                'audio',
                Type\ChoiceType::class,
                [
                    'choices' => [
                        'Dub' => 'Dub',
                        'Raw' => 'Raw',
                        'Dual Language' => 'Dual Language',
                    ],
                    'label' => 'Audio Language',
                    'attr' => ['tabindex' => 2],
                    'compound' => false,
                ]
            )
            ->add('subtitle', SubtitleLanguageType::class)
            ->add(
                'link',
                Type\TextType::class,
                [
                    'attr' => [
                        'tabindex' => 2,
                        'class' => 'inputbox autowidth',
                        'size' => 45
                    ],
                    'help' => 'Reference link is required. '
                    . 'Try searching <a id="mal-link" href="https://myanimelist.net/" target="_blank"><strong>myanimelist</strong></a>.'
                    . $js,
                    'constraints' => [new Assert\Url([ 'relativeProtocol' => false, ])],
                    'help_html' => true,
                ]
            )
            ->add(
                'requestType',
                HiddenType::class,
                [ 'attr' => ['value' => self::$alias] ]
            );
    }
}
