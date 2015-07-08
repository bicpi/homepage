<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Gregwar\Captcha\CaptchaBuilder;
use App\Twig\AssetVersionExtension;
use App\Twig\MarkdownExtension;
use Symfony\Component\Translation\Loader\YamlFileLoader;

$app = new Silex\Application();
$app['parameters'] = Yaml::parse(
    file_get_contents(__DIR__.'/../src/Resources/config/parameters.yml')
);
$app['debug'] = $app['parameters']['debug'];
if ($app['debug']) {
    ini_set('display_errors', true);
    error_reporting(-1);
}

$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => dirname(__DIR__) . '/src/Resources/views',
    'twig.form.templates' => ['form_div_layout.html.twig', 'form_layout.twig']
]);
$app['twig'] = $app->extend("twig", function (\Twig_Environment $twig, Silex\Application $app) {
    $twig->addExtension(new AssetVersionExtension(dirname(__DIR__) . '/src'));
    $twig->addExtension(new MarkdownExtension(new \Parsedown()));

    return $twig;
});
$app->register(new Silex\Provider\SwiftmailerServiceProvider(), [
    'swiftmailer.options' => [
        'host' => $app['parameters']['mailer_host'],
        'port' => $app['parameters']['mailer_port'],
        'username' => $app['parameters']['mailer_username'],
        'password' => $app['parameters']['mailer_password'],
        'encryption' => $app['parameters']['mailer_encryption'],
        'auth_mode' => $app['parameters']['mailer_auth_mode'],
    ]
]);
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), [
    'locale' => 'en',
    'locale_fallbacks' => ['en'],
]);
$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());
    $translator->addResource('yaml', __DIR__.'/../src/Resources/translations/en.yml', 'en');
    $translator->addResource('yaml', __DIR__.'/../src/Resources/translations/de.yml', 'de');

    return $translator;
}));
$app->register(new Silex\Provider\FormServiceProvider());
$app['captcha'] = function () {
    return new CaptchaBuilder();
};

$app
    ->match('/', function () use ($app) {
        return $app->redirect($app['url_generator']->generate('home', ['_locale' => 'en']), 301);
    })
    ->method('GET');

$app->match('/{_locale}', function (Request $request) use ($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add('name', 'text', ['constraints' => [
                new Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => $app['translator']->trans('contact.validation.name.not_blank')
                ]),
                new Symfony\Component\Validator\Constraints\Length([
                    'min' => 2,
                    'minMessage' => $app['translator']->trans('contact.validation.name.min')
                ]),
            ]
            ]
        )
        ->add('email', 'email', ['constraints' => [
                new Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => $app['translator']->trans('contact.validation.email.not_blank')
                ]),
                new Symfony\Component\Validator\Constraints\Email([
                    'message' => $app['translator']->trans('contact.validation.email.email')
                ]),
            ]
            ]
        )
        ->add('message', 'textarea', ['constraints' => [
                new Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => $app['translator']->trans('contact.validation.message.not_blank')
                ]),
                new Symfony\Component\Validator\Constraints\Length([
                    'min' => 10,
                    'minMessage' => $app['translator']->trans('contact.validation.message.min')
                ]),
            ]
            ]
        )
        ->add('captcha', 'text', ['constraints' => [
                new Symfony\Component\Validator\Constraints\EqualTo([
                    'value' => $app['session']->get('captcha', ''),
                    'message' => $app['translator']->trans('contact.validation.captcha.invalid')
                ]),
            ]
            ]
        )
        ->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $message = \Swift_Message::newInstance('Nachricht von der Homepage')
                ->setFrom(['hello@philipp-rieber.net' => 'philipp-rieber.net'])
                ->setTo('hello@philipp-rieber.net')
                ->setReplyTo($data['email'])
                ->setBody(
                    sprintf(
                        "Homepage-Nachricht von %s <%s>\n\n\n%s",
                        $data['name'],
                        $data['email'],
                        $data['message']
                    )
                );
            $app['mailer']->send($message);

            $app['session']->getFlashBag()->add(
                'success',
                $app['translator']->trans('contact.thanks', [
                        '%name%' => $app->escape($data['name'])
                    ])
            );

            return $app->redirect($app['url_generator']->generate('home', ['_locale' => 'en']).'#');
        }
    }

    $skillsRaw = Yaml::parse(
        file_get_contents(dirname(__DIR__).'/src/Resources/config/skills.yml')
    );
    $skills = [];
    foreach ($skillsRaw as $weight => $skillGroup) {
        foreach ($skillGroup as $skill) {
            $skills[$skill] = $weight;
        }
    }

    shuffle_assoc($skills);

    $birthDate = new \DateTimeImmutable('1979-02-06');
    $captcha = $app['captcha']->build();
    $app['session']->set('captcha', $captcha->getPhrase());

    return $app['twig']->render('home.twig', [
            'age' => $birthDate->diff(new DateTimeImmutable('now'))->y,
            'skills' => $skills,
            'form' => $form->createView(),
            'captcha' => $captcha,
        ]);
    })
    ->bind('home')
    ->assert('_locale', 'en|de')
    ->method('GET|POST');

$app
    ->match('/{_locale}/imprint-and-privacy', function (Request $request) use ($app) {
        return $app['twig']->render(sprintf('notes.%s.html.twig', $request->getLocale()), []);
    })
    ->bind('notes')
    ->assert('_locale', 'en|de')
    ->method('GET');

$app->run();


function shuffle_assoc(&$array) {
    $keys = array_keys($array);

    shuffle($keys);

    foreach($keys as $key) {
        $new[$key] = $array[$key];
    }

    $array = $new;

    return true;
}
