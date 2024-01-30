<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BeerRepository;
use App\Repository\RemovedCountryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Intl\Countries;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
  #[Route('/', name: 'app_index')]
  public function index(Request $request, BeerRepository $respository): Response
  {
    $beerName = $request->query->get('name', null);
    $page = $request->query->get('page', 1);

    $limit = $request->query->get('limit', 50);
    if ($request->cookies->has('limit')) {
      $limit = $request->cookies->get('limit');
    }

    $offset = $limit * max($page - 1, 0);

    if ($beerName) {
      $beerName = trim($beerName);
      $beers = $respository->getBeersWithName($beerName);
      $totalbeers = count($beers);
    } else {
      $beers = $respository->getAllBeersByDateAdded($limit, $offset);
      $totalbeers = $respository->getTotalNumBeers();
    }

    return $this->render(
      'default/index.html.twig',
      [
        'beers' => $beers,
        'numbeers' => $totalbeers,
        'limit' => $limit,
        'page' => $page,
      ]
    );
  }

  #[Route('/map', name: 'app_map')]
  public function mapIndex(
    BeerRepository $respository,
    RemovedCountryRepository $countryRepository
  ): Response {
    return $this->render('default/map.html.twig', [
      'countries' => $respository->getCountiesBeerCount(),
      'signatures' => $respository->getAllSignatureBeers(),
      'total_countries' => count(Countries::getNames()),
      'removed_countries' => $countryRepository->findAll(),
      'country_names' => Countries::getNames(),
      'mapbox_token' => $this->getParameter('mapbox_token')
    ]);
  }

  #[Route('/stats', name: 'app_stats')]
  public function statsIndex(BeerRepository $beerRespository): Response
  {
    return $this->render('default/stats.html.twig', [
      'beersbydate' => $beerRespository->getTotalBeersByDate()
    ]);
  }

  #[Route('/about', name: 'app_about')]
  public function aboutIndex(): Response
  {
    return $this->render('default/about.html.twig');
  }

  #[Route('/signatures', name: 'app_signatures')]
  public function signaturesIndex(BeerRepository $beerRespository): Response
  {
    return $this->render('default/signatures.html.twig', [
      'beers' => $beerRespository->getAllSignatureBeers()
    ]);
  }

  #[Route('/contributors', name: 'app_contributors')]
  public function contributorsIndex(
    BeerRepository $beerRespository,
    UserRepository $userRepository
  ): Response {
    return $this->render('default/contributors.html.twig', [
      'contributors' => $beerRespository->getContributorsTotalBeers($userRepository),
    ]);
  }

  #[Route('/user/{id}', name: 'app_show_user', requirements: ['id' => '\d+'])]
  public function showUser(User $user, BeerRepository $respository): Response
  {
    return $this->render('default/user.html.twig', [
      'user' => $user,
      'beers' => $respository->getAllBeersForUser($user)
    ]);
  }

  #[Route(
    '/country/{country}',
    name: 'app_show_country',
    requirements: ['country' => '\w+']
  )]
  public function showCountry(string $country, BeerRepository $respository): Response
  {
    return $this->render('default/country.html.twig', [
      'country' => strtoupper($country),
      'name' => Countries::getName($country),
      'beers' => $respository->getAllBeersForCountry($country)
    ]);
  }
}
