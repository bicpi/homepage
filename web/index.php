<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Neutron\ReCaptcha\ReCaptcha;
use Neutron\ReCaptcha\ReCaptchaServiceProvider;

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
$app['parameters'] = Yaml::parse(__DIR__.'/../src/config/parameters.yml');
$app['debug'] = $app['parameters']['debug'];

$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => dirname(__DIR__) . '/src/views',
        'twig.form.templates' => array('form_div_layout.html.twig', 'form_layout.twig')
    )
);
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
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(
    new Silex\Provider\TranslationServiceProvider(),
    array(
        'locale' => 'de',
    )
);
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new ReCaptchaServiceProvider(), array(
    'recaptcha.public-key'  => $app['parameters']['recaptcha_public_key'],
    'recaptcha.private-key' => $app['parameters']['recaptcha_private_key'],
));

$app->before(
    function () use ($app) {
        $app['translator']->addLoader('xlf', new Symfony\Component\Translation\Loader\XliffFileLoader());
        $app['translator']->addResource(
            'xlf',
            dirname(
                __DIR__
            ) . '/vendor/symfony/validator/Symfony/Component/Validator/Resources/translations/validators.de.xlf',
            'de',
            'validators'
        );
    }
);

$app->match(
    '/',
    function (Symfony\Component\HttpFoundation\Request $request) use ($app) {
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
            ->getForm();

        $recaptchaHasError = false;
        if ('POST' == $request->getMethod()) {
            $recaptchaHasError = !$app['recaptcha']->bind($app['request'])->isValid();
            $form->bind($request);
            if (!$recaptchaHasError && $form->isValid()) {
                $data = $form->getData();

                $message = \Swift_Message::newInstance('Nachricht von der Homepage')
                    ->setFrom(array('info@philipp-rieber.net' => 'philipp-rieber.net'))
                    ->setTo('info@philipp-rieber.net')
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

        $skillsRaw = \Symfony\Component\Yaml\Yaml::parse(dirname(__DIR__).'/src/config/skills.yml');
        $skills = array();
        foreach ($skillsRaw as $weight => $skillGroup) {
            foreach ($skillGroup as $skill) {
                $skills[$skill] = $weight;
            }
        }

        shuffle_assoc($skills);

        $birthDate = new \DateTime('1979-02-06');

        return $app['twig']->render('home.twig', array(
                'age' => $birthDate->diff(new DateTime('now'))->y,
                'skills' => $skills,
                'form' => $form->createView(),
                'recaptchaPubKey' => $app['parameters']['recaptcha_public_key'],
                'recaptchaHasError' => $recaptchaHasError,
        ));
    }
)
    ->method('GET|POST');;

$app->run();
