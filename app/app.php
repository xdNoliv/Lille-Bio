<?php

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// Register global error and exception handlers
ErrorHandler::register();
ExceptionHandler::register();

// Register service providers.
$app->register(new Silex\Provider\DoctrineServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\LocaleServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\RoutingServiceProvider());
$app->register(new Silex\Provider\SwiftmailerServiceProvider());
$app->register(new Silex\Provider\AssetServiceProvider(), array(
    'assets.version' => 'v1'
));

$app['swiftmailer.options'] = array(
    'host' => 'smtp.gmail.com',
    'port' => '465',
    'username' => 'lillebio.cnam@gmail.com',
    'password' => 'cnamweb2017',
    'encryption' => 'ssl',
    'auth_mode' => 'login'
);

// Register services.
$app['model.restaurant'] = function ($app) {
    return new LilleBio\Model\Restaurant($app['db']);
};
$app['model.recherche'] = function ($app) {
    return new LilleBio\Model\Recherche($app['db']);
};
$app['model.reservation'] = function ($app) {
    return new LilleBio\Model\Reservation($app['db']);
};
$app['model.restaurateur'] = function ($app) {
    return new LilleBio\Model\Restaurateur($app['db']);
};