<?php

namespace jeb\snahp\Apps\RequestForm;

require_once '/var/www/forum/ext/jeb/snahp/core/functions_forums.php';

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

use \Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use \Symfony\Bridge\Twig\Extension\FormExtension;
use \Symfony\Component\Form\FormFactoryBuilder;
use \Symfony\Component\Form\FormRenderer;
use \Symfony\Component\Form\Forms;
use \Symfony\Component\Validator\Validation;

use jeb\snahp\Apps\RequestForm\Types as Type;
use \Symfony\Component\Form\Extension\Core\Type\ChoiceType;



const FORM_TYPE_REGISTRY = [
    // form names & request forum names are used interchangeably
    // app, game, misc, movie, music, tv
    // alias='' prevents input name with brackets like <input name="form[host]">
    'anime' => ['classname' => Type\AnimeType::class, 'alias' => 'requestform'],
    'ebook' => ['classname' => Type\EbookType::class, 'alias' => 'requestform'],
    'game' => ['classname' => Type\GameType::class, 'alias' => 'requestform'],
    // 'app' => ['classname' => Type\AppType::class, 'alias' => 'requestform'],
    // 'misc' => ['classname' => Type\MiscType::class, 'alias' => 'requestform'],
    // 'movie' => ['classname' => Type\MovieType::class, 'alias' => 'requestform'],
    // 'music' => ['classname' => Type\MusicType::class, 'alias' => 'requestform'],
    // 'tv' => ['classname' => Type\TvType::class, 'alias' => 'requestform'],
];
const NULL_TYPE_DEFINITION = ['classname' => Type\NullType::class, 'alias' => 'null'];

class RequestFormHelper
{
    protected $request;
    protected $template;
    public function __construct(
        $request,
        $template
    ) {
        $this->request = $request;
        $this->template = $template;
    }

    public function makeRequestBBCode($form, $extra=[])
    {
        if (!$form) {
            return;
        }
        $data = $form->getData();
        $data->data = array_merge($data->data, $extra);
        return $form->getData()->makeBBCode();
    }

    public function embedCustomForm($forumId)
    {
        $form = $this->createFormByRequestForumId($forumId);
        $this->isValid($form);
        $renderer = $this->getRenderer();
        $html = $renderer->render(
            'form.html.twig',
            [ 'form' => $form->createView(), ]
        );
        $this->template->assign_var('CUSTOM_FORM_ELEMENTS', $html);
    }

    public function createFormByRequestForumId($forumId)
    {
        $requestForumName = getRequestForumName((int) $forumId);
        return $this->createFormByForumName($requestForumName);
    }

    public function createFormByForumName($forumName)
    {
        $formTypeDef = getDefault(FORM_TYPE_REGISTRY, $forumName, NULL_TYPE_DEFINITION);
        return $this->createForm($formTypeDef);
    }

    public function createForm($formTypeDef)
    {
        $validator = Validation::createValidator();
        $validatorExtensions = [new ValidatorExtension($validator)];
        $formFactoryBuilder = new FormFactoryBuilder();
        $dataClassname = $formTypeDef['classname']::CLASSNAME;
        $data = new $dataClassname();
        return $formFactoryBuilder
            ->addExtensions($validatorExtensions) // Required for "constraints"
            ->getFormFactory()
            ->createNamed(
                $name = $formTypeDef['alias'],
                $type = $formTypeDef['classname'],
                $data = $data,
                $options = ['allow_extra_fields' => true]
            );
    }

    public function isValid($form)
    {
        $this->handleFormRequest($form);
        if ($form->isSubmitted() && $form->isValid()) {
            return true;
        }
        return false;
    }

    public function handleFormRequest($form)
    {
        // This function sets form flags for isSubmitted() and isValid()
        // $form->handleRequest uses the superglobal $_REQUEST
        // must enable super globals to suspend phpbb gatekeeper
        $this->request->enable_super_globals();
        $form->handleRequest();
        $this->request->disable_super_globals();
    }

    public function getRenderer()
    {
        // Renderer is what turns form object into html string
        $defaultFormTheme = 'form_div_layout.html.twig';
        $templateDir = '/var/www/forum/ext/jeb/snahp/styles/all/template';
        $twig = new \Twig\Environment(
            new \Twig\Loader\FilesystemLoader(
                [
                __DIR__.'/styles',
                $templateDir.'/_forms/twig-bridge/Resources/Form',
                ]
            )
        );
        $formEngine = new \Symfony\Bridge\Twig\Form\TwigRendererEngine([$defaultFormTheme], $twig);
        $twig->addRuntimeLoader(
            new \Twig\RuntimeLoader\FactoryRuntimeLoader(
                [
                    \Symfony\Component\Form\FormRenderer::class => function () use ($formEngine, $csrfManager) {
                        return new \Symfony\Component\Form\FormRenderer($formEngine, $csrfManager);
                    },
                ]
            )
        );
        $twig->addExtension(new FormExtension());
        return $twig;
    }

    public function collectErrors($form)
    {
        $errors = [];
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form as $elem) {
            $errors = array_merge($errors, $this->collectErrors($elem));
        }
        return $errors;
    }

    public function removeRequestBBCode($text)
    {
        return preg_replace('#\[request](.*?)\[\/request]#s', '', $text);
    }

    public function tagTopicTitle($title, $form)
    {
        include_once '/var/www/forum/ext/jeb/snahp/core/functions_string.php';
        $tagFields = array_reverse(['platform',]);
        $first = false;
        foreach ($tagFields as $tag) {
            if (!$form->has($tag)) {
                continue;
            }
            $childForm = $form[$tag];
            if ($childForm) {
                $value = $childForm->getData();
                $title = removeTags($title, $value);
                $title = prependTag($title, $value, $suffix = $first ? '' : ' ');
                $first = true;
            }
        }
        return $title;
    }
}
