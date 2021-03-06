<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\UrlGeneratorServiceProvider;

// Home page
$app->get('/', function () use ($app) {
	$app['session']->remove('timestamp');
	// Requete qui récupère les trois derniers restau avec leurs images et catégories
	$troisDerniersRestau = $app['model.restaurant']->troisDerniersRestau();
	$trenteProchainsJours = $app['model.recherche']->trenteProchainsJours();
	$listeCategories = $app['model.recherche']->listeCategories();
	$listeCategorieImg = $app['model.restaurant']->categorieImages();
	// Appel de la vue page d'accueil
    return $app['twig']->render('index.html.twig', array('listeRestau' => $troisDerniersRestau, 'date' => $trenteProchainsJours, 'listeCat' => $listeCategories, 'listeCatImg' => $listeCategorieImg));

})->bind('home');

// Aide utilisateurs
$app->get('/aide-utilisateurs', function () use ($app) {
	return $app['twig']->render('aide-utilisateur.html.twig');
})->bind('aide_utilisateurs');

// Restaurant
$app->get('/restaurant/{id}', function ($id) use ($app) {

	$app['session']->set('timestamp', $app['session']->get('timestamp'));

	$trenteProchainsJours = $app['model.recherche']->trenteProchainsJours();
	$app['session']->set('date', $trenteProchainsJours);
	$pageRestau = $app['model.restaurant']->getRestau($id);
	return $app['twig']->render('restaurant.html.twig', array('dataRest' => $pageRestau));
})->bind('restaurant');

// Toutes les catégories
$app->post('/resultats', function (Request $request) use ($app) {
	return $app['twig']->render('resultats.html.twig');
})->bind('resultats');

// Restaurant
$app->post('/exemple', function (Request $request) use ($app) {
	$exemple = $app['model.restaurant']->exemple($request);
	return var_dump($exemple);
})->bind('exemple');

// Espace restaurateur
$app->get('/espace-restaurateur', function () use ($app) {
	$id = $app['session']->get('numRest');
	$listeCategories = $app['model.recherche']->listeCategories();
	$pageRestau = $app['model.restaurant']->getRestau($id);
	return $app['twig']->render('espace-restaurateur.html.twig', array('data' => $pageRestau,'liste_categorie' => $listeCategories));
})->bind('espace_restau');

// Recherche restau
$app->post('/recherche-restaurant', function (Request $request) use ($app) {

	$app['session']->set('timestamp', $request->request->get('date'));

	$trenteProchainsJours = $app['model.recherche']->trenteProchainsJours();
	$app['session']->set('date', $trenteProchainsJours);

	$listeRestau = $app['model.recherche']->rechercheRestau($request);

	return $app['twig']->render('liste-restaurant.html.twig', array('listeRestau' => $listeRestau));

})->bind('recherche_restau');

// Liste restaurant
$app->get('/liste-restaurants', function () use ($app) {
	$listeRestau = $app['model.restaurant']->tousLesRestau();
	return $app['twig']->render('liste-restaurant.html.twig', array('listeRestau' => $listeRestau));
})->bind('tous_restau');

// Liste restaurant par catégorie
$app->get('/liste-restaurants/{categorie}', function ($categorie) use ($app) {
	$listeRestau = $app['model.restaurant']->RestaurantparCategorie($categorie);
	return $app['twig']->render('liste-restaurant.html.twig', array('listeRestau' => $listeRestau));
})->bind('tous_restau_categorie');

////////////////////////////
///// RESERVATIONS /////////
////////////////////////////

// Script réservation
$app->post('/reservation', function (Request $request) use ($app) {

	// Déclanchement du script de réservation
	$reservation = $app['model.reservation']->reserver($request);
	// Si la réservation a échouée
	if (false === $reservation) {
		$app['session']->getFlashBag()->add('msg_success', 'Erreur lors de la réservation. Le restaurant est peut-être complet à la date et/ou à l\'heure précisée. Réessayez en modifiant ces informations ou choisissez un autre restaurant.');
		$numRest = $request->request->get('numRest');
		return $app->redirect($app["url_generator"]->generate("restaurant", [
        "id" => $numRest]));
	}

	// Si la réservation est OK
	// Préparation du message de confirmation
	$subject = '[LilleBio] Confirmation de réservation';
    $to = $request->request->get('email');
    $message = 'Bonjour,<br><br>Nous avons le plaisir de vous confirmer votre réservation pour le <strong>'.date("d/m/Y", $request->request->get('date')).' à '.$request->request->get('heure').'</strong> dans le restaurant : <strong>'.$reservation['nomRest'].'</strong>.<br><br>Vous avez réservé pour <strong>'.$request->request->get('nbPers').' personne(s)</strong> au nom de <strong>'.$request->request->get('prenom').' '.$request->request->get('nom').'</strong>.<br><br>Votre numéro de réservation est le <strong>'.$reservation['numResa'].'</strong>. Vous pouvez consulter et modifier ou supprimer cette réservation sur le site dans la rubrique <a href="http://lillebio-cnam.pe.hu/mes-reservations/recherche">"mes réservations"</a> en y précisant l\'adresse email utilisée lors de votre réservation et ce numéro.<br><br>Bon appétit et à bientôt sur notre site !!<br><br>L\'équipe LilleBio';
    // Pour envoyer un mail HTML, l'en-tête Content-type doit être défini
    $header  = "Content-type: text/html; charset=UTF-8" . "\r\n";

    // Essai d'envoi du message
    // Si le message est bien envoyé
    if (mail($to, $subject, $message, $header)) {
    	// Enregistrement du message de confirmation en SESSION
        $app['session']->getFlashBag()->add('msg_success', 'La réservation a bien été enregistrée. Vous allez recevoir un email de confirmation à l\'adresse mentionnée');
        // Redirection vers la page d'accueil avec affichage du msg de confirmation
    	return $app->redirect($app["url_generator"]->generate("home"));
    }
})->bind('reservation');// Fin du script de réservation

// Mes reservations
$app->post('/mes-reservations', function (Request $request) use ($app) {
	$result = $app['model.reservation']->rechercheReservation($request);
	return $app['twig']->render('mes-reservations.html.twig', array('result' => $result));
})->bind('mes_reservations');

// Recherche reservation
$app->get('/mes-reservations/recherche', function (Request $request) use ($app) {
	return $app['twig']->render('formulaire/mes-reservations.html.twig');
})->bind('recherche_reservations');

// Modif reservations
$app->post('/modif-resa', function (Request $request) use ($app) {
	$result = $app['model.reservation']->modifReservation($request);
	$app['session']->getFlashBag()->add('msg_success', 'La réservation a bien été mise à jour ;)');
	return $app['twig']->render('mes-reservations.html.twig', array('result' => $result));
})->bind('modif_resa');

// Supprimer reservation
$app->get('/suppr-resa/{id}', function ($id) use ($app) {
	$result = $app['model.reservation']->supprReservation($id);
	if ($result) {
		$app['session']->getFlashBag()->add('msg_suppr', 'La réservation a bien été supprimée ;)');
		return $app->redirect($app["url_generator"]->generate("home"));
	} else {
		$app['session']->getFlashBag()->add('msg_suppr', 'Une erreur est survenue lors de la supression, veuillez réessayer :(');
		return $app['twig']->render('formulaire/mes-reservations.html.twig');
	}
})->bind('suppr_resa');

///////////////////////////////////
///// ESPACE RESTAURATEUR /////////
///////////////////////////////////

// Inscription restaurateur
$app->get('/inscription-restaurateur', function (Request $request) use ($app) {
	$listeCategories = $app['model.recherche']->listeCategories();
	return $app['twig']->render('inscription-restaurateur.html.twig', array('listeCat' => $listeCategories));
})->bind('inscription_restaurateur');

// Enregistrer restaurateur
$app->post('/enregistrer-restaurateur', function (Request $request) use ($app) {
	$restau = $app['model.restaurateur']->enregistrerRestau($request);
	if (false !== $restau) {
		$app['session']->set('numRest', $restau['numRest']);
		return $app->redirect($app["url_generator"]->generate("espace_restau"));
	}
})->bind('enregistrer_restau');


// Inscription restaurateur
$app->post('/enregistrer_coordonnees', function (Request $request) use ($app) {
	$insertionCoordonnees = $app['model.restaurateur']->infosrestaurateur($request);
	if ($insertionCoordonnees){return $app->redirect($app["url_generator"]->generate("espace_restau"));};
})->bind('enregistrer_coordonnees');


// Insertion des entrées
$app->post('/enregistrer_entree', function (Request $request) use ($app) {
	$infosEntrees = $app['model.restaurateur']->infosEntrees($request);
	if ($infosEntrees){return $app->redirect($app["url_generator"]->generate("espace_restau"));};
})->bind('enregistrer_entree');

// Insertion des plats
$app->post('/enregistrer_plat', function (Request $request) use ($app) {
	$infosPlats = $app['model.restaurateur']->infosPlats($request);
	if ($infosPlats){return $app->redirect($app["url_generator"]->generate("espace_restau"));};
})->bind('enregistrer_plat');

$app->post('/enregistrer_dessert', function (Request $request) use ($app) {
	$infosDesserts = $app['model.restaurateur']->infosDesserts($request);
	if ($infosDesserts){return $app->redirect($app["url_generator"]->generate("espace_restau"));};
})->bind('enregistrer_dessert');

$app->post('/enregistrer_menu1', function (Request $request) use ($app) {
	$infosMenu1 = $app['model.restaurateur']->infosMenu1($request);
	if ($infosMenu1){return $app->redirect($app["url_generator"]->generate("espace_restau"));};
})->bind('enregistrer_menu1');



// form login
$app->get('/connexion', function () use ($app) {
	return $app['twig']->render('formulaire/connexion.html.twig');
})->bind('connexion');
// connexion redirection
$app->post('/verifconnexion', function(Request $request) use($app) {
	$etat = $app['model.restaurateur']->connexion($request);
	if ($etat){
		$app['session']->set('numRest', $etat);
		return $app->redirect($app["url_generator"]->generate("espace_restau"));
	}
})->bind('verifconnexion');


///////////////////////////////////
///// AUTRES PAGES ///////////////
//////////////////////////////////

// Page des mentions légales
$app->get('/mentions-legales', function () use ($app) {
	return $app['twig']->render('mentions-legales.html.twig');
})->bind('mentions_legales');

