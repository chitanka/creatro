<?php
$rootDir = __DIR__.'/..';
require_once "$rootDir/vendor/autoload.php";

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['root.dir'] = $rootDir;
$app['app.dir'] = $rootDir.'/app';
$app['debug'] = false;

$app->register(new \Silex\Provider\TwigServiceProvider(), array(
	'twig.path'    => $app['app.dir'].'/views',
	'twig.options' => array(
		'cache'            => ($app['debug'] ? false : $app['app.dir'].'/cache/twig'),
		'strict_variables' => true,
		'debug'            => $app['debug'],
	),
));
$app->register(new \Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new \Silex\Provider\SwiftmailerServiceProvider());

$app->get('/', function() use ($app) {
	return $app['twig']->render('index.html.twig');
})->bind('home');

$app->get('/project/{project}', function($project) use ($app) {
	return $app->redirect('/');
	return $app['twig']->render('project.html.twig', array(
		'project' => $project,
	));
});

$app->get('/projects/new', function() use ($app) {
	return $app['twig']->render('projects-create.html.twig');
});
$app->get('/projects/original', function() use ($app) {
	return $app->redirect('/');
	return $app['twig']->render('projects-original.html.twig');
})->bind('projects-original');
$app->get('/projects/translations', function() use ($app) {
	return $app->redirect('/');
	return $app['twig']->render('projects-translations.html.twig');
})->bind('projects-translations');

$app->get('/author/{author}', function($author) use ($app) {
	return $app->redirect('/');
	return $app['twig']->render('author.html.twig', array(
		'author' => $author,
	));
});
$app->get('/genre/{genre}', function($genre) use ($app) {
	return $app->redirect('/');
});

$app->get('/about', function() use ($app) {
	return $app['twig']->render('about.html.twig');
})->bind('about');

$app->get('/contact', function() use ($app) {
	return $app['twig']->render('contact.html.twig');
})->bind('contact');
$app->post('/contact', function(Request $request) use ($app) {
	$config = require $app['app.dir'].'/config/mailer.php';
	$app['swiftmailer.options'] = $config['options'];

	$messageBody = $request->get('message');
	if (substr_count($messageBody, 'http') > 1) {
		return $app['twig']->render('contact.html.twig', array(
			'error_message' => 'Съобщението ви е определено като спам, защото съдържа повече от един уеб адрес.',
		));
	}

	$message = \Swift_Message::newInstance()
		->setSubject('[Авторско ателие] '.$request->get('title'))
		->setFrom(array($request->get('email') => $request->get('name')))
		->setTo(array($config['recipient']))
		->setBody($request->get('message'));
	$app['mailer']->send($message);

	return $app['twig']->render('contact.html.twig', array(
		'success_message' => 'Съобщението ви беше получено.',
	));
});

$app->run();
