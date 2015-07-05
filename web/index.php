<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Michelf\MarkdownExtra;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Gregwar\Captcha\CaptchaBuilder;
use App\Twig\AssetVersionExtension;
use App\Twig\MarkdownExtension;
use Symfony\Component\Translation\Loader\YamlFileLoader;

function shuffle_assoc(&$array) {
    $keys = array_keys($array);

    shuffle($keys);

    foreach($keys as $key) {
        $new[$key] = $array[$key];
    }

    $array = $new;

    return true;
}

$app = new Silex\Application();
$app['parameters'] = Yaml::parse(
    file_get_contents(__DIR__.'/../src/Resources/config/parameters.yml')
);
$app['debug'] = $app['parameters']['debug'];
if ($app['debug']) {
    ini_set('display_errors', true);
    error_reporting(-1);
}

$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => dirname(__DIR__) . '/src/Resources/views',
        'twig.form.templates' => array('form_div_layout.html.twig', 'form_layout.twig')
    )
);
$app['twig'] = $app->extend("twig", function (\Twig_Environment $twig, Silex\Application $app) {
    $twig->addExtension(new AssetVersionExtension(dirname(__DIR__) . '/src'));
    $twig->addExtension(new MarkdownExtension(new \Parsedown()));

    return $twig;
});
$app->register(new Silex\Provider\SwiftmailerServiceProvider(), array(
    'swiftmailer.options' => array(
        'host' => $app['parameters']['mailer_host'],
        'port' => $app['parameters']['mailer_port'],
        'username' => $app['parameters']['mailer_username'],
        'password' => $app['parameters']['mailer_password'],
        'encryption' => $app['parameters']['mailer_encryption'],
        'auth_mode' => $app['parameters']['mailer_auth_mode'],
    )
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(
    new Silex\Provider\TranslationServiceProvider(),
    array(
        'locale' => 'en',
        'locale_fallbacks' => array('en'),
    )
);
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

$homepage = function (Request $request) use ($app) {
    $form = $app['form.factory']->createBuilder('form')
        ->add(
            'name',
            'text',
            array(
                'constraints' => array(
                    new Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Bitte geben Sie ihren Namen ein')),
                    new Symfony\Component\Validator\Constraints\Length(array(
                        'min' => 2,
                        'minMessage' => 'Bitte geben Sie mindestens {{ limit }} Zeichen ein'
                    )),
                )
            )
        )
        ->add(
            'email',
            'email',
            array(
                'constraints' => array(
                    new Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Bitte geben Sie eine E-Mail-Adresse ein')),
                    new Symfony\Component\Validator\Constraints\Email(array('message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein')),
                )
            )
        )
        ->add(
            'message',
            'textarea',
            array(
                'constraints' => array(
                    new Symfony\Component\Validator\Constraints\NotBlank(array('message' => 'Bitte geben Sie eine Nachricht ein')),
                    new Symfony\Component\Validator\Constraints\Length(array(
                        'min' => 10,
                        'minMessage' => 'Bitte geben Sie mindestens {{ limit }} Zeichen ein'
                    ))
                )
            )
        )
        ->add(
            'captcha',
            'text',
            array(
                'constraints' => array(
                    new Symfony\Component\Validator\Constraints\EqualTo(array(
                        'value' => $app['session']->get('captcha', ''),
                        'message' => 'Die Zeichen haben nicht übereingestimmt, bitte versuchen Sie es erneut'
                    )),
                )
            )
        )
        ->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $message = \Swift_Message::newInstance('Nachricht von der Homepage')
                ->setFrom(array('hello@philipp-rieber.net' => 'philipp-rieber.net'))
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
                sprintf(
                    '<b>%s</b>, vielen Dank für ihre Nachricht.',
                    $app->escape($data['name'])
                )
            );

            return $app->redirect('/#');
        }
    }

    $skillsRaw = Yaml::parse(
        file_get_contents(dirname(__DIR__).'/src/Resources/config/skills.yml')
    );
    $skills = array();
    foreach ($skillsRaw as $weight => $skillGroup) {
        foreach ($skillGroup as $skill) {
            $skills[$skill] = $weight;
        }
    }

    shuffle_assoc($skills);

    $birthDate = new \DateTime('1979-02-06');
    $captcha = $app['captcha']->build();
    $app['session']->set('captcha', $captcha->getPhrase());

    return $app['twig']->render('home.twig', array(
            'age' => $birthDate->diff(new DateTime('now'))->y,
            'skills' => $skills,
            'form' => $form->createView(),
            'captcha' => $captcha,
        ));
};

$app->match('/{_locale}', $homepage)
    ->bind('home_locale')
    ->assert('_locale', 'de')
    ->method('GET|POST');
$app
    ->match('/', $homepage)
    ->bind('home')
    ->method('GET|POST');

$app->run();
