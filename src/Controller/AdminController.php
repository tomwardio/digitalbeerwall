<?php

namespace App\Controller;

use App\Entity\Beer;
use App\Entity\RemovedCountry;
use App\Entity\User;
use App\Repository\BeerRepository;
use App\Repository\RemovedCountryRepository;
use App\Repository\UserRepository;
use Aws\S3\S3Client;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class AdminController extends AbstractController
{
  #[Route('/admin', name: 'app_admin')]
  public function index(
    BeerRepository $beerRepository,
    UserRepository $userRepository,
    RemovedCountryRepository $countryRepository,
    RoleHierarchyInterface $roleHierarchy,
  ): Response {
    return $this->render('admin/index.html.twig', [
      'users' => $userRepository->findAll(),
      'deleted_beers' => $beerRepository->getAllDeletedBeers(),
      'role_hierarchy' => $roleHierarchy,
      'removed_countries' => $countryRepository->findAll(),
    ]);
  }

  #[Route('/admin/user/{id}', name: 'app_admin_edit_user', requirements: ['id' => '\d+'])]
  public function editUser(
    Request $request,
    User $user,
    RoleHierarchyInterface $roleHierarchy,
    EntityManagerInterface $entityManager
  ): Response {
    $is_contributor = $user->hasRole('ROLE_CONTRIBUTOR', $roleHierarchy);
    $is_admin = $user->hasRole('ROLE_ADMIN', $roleHierarchy);
    $is_super_admin = $user->hasRole('ROLE_SUPER_ADMIN', $roleHierarchy);

    $builder = $this->createFormBuilder($user);

    if (!$is_super_admin) {
      if (!$is_admin) {
        $builder->add('contributor', SubmitType::class, array(
          'label' => ($is_contributor ? 'Revoke Contributor' : 'Make Contributor'),
          'attr' => array(
            'class' => ($is_contributor ? 'btn btn-danger' : 'btn btn-primary'),
          )
        ));
      }

      $builder->add('admin', SubmitType::class, array(
        'label' => ($is_admin ? 'Revoke Admin' : 'Make Admin'),
        'attr' => array(
          'class' => ($is_admin ? 'btn btn-danger' : 'btn btn-primary'),
        )
      ));

      $builder->add('lock', SubmitType::class, array(
        'label' => ($user->isLocked() ? 'Unlock' : 'Lock'),
        'attr' => array(
          'class' => ($user->isLocked() ? 'btn btn-primary' : 'btn btn-danger'),
        )
      ));
    }

    /** @var Form $form  */
    $form = $builder->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      if (
        $form->has('contributor') &&
        $form->getClickedButton() === $form->get('contributor')
      ) {
        in_array('ROLE_CONTRIBUTOR', $user->getRoles()) ?
          $user->removeRole('ROLE_CONTRIBUTOR') : $user->addRole('ROLE_CONTRIBUTOR');
      } elseif ($form->getClickedButton() === $form->get('admin')) {
        in_array('ROLE_ADMIN', $user->getRoles()) ?
          $user->removeRole('ROLE_ADMIN') : $user->addRole('ROLE_ADMIN');
      } elseif ($form->getClickedButton() === $form->get('lock')) {
        $user->setLocked(!$user->isLocked());
      }

      $entityManager->persist($user);
      $entityManager->flush();

      return $this->redirect($this->generateUrl(
        'app_admin_edit_user',
        array("id" => $user->getId())
      ));
    }
    return $this->render('admin/user.html.twig', [
      'user' => $user,
      'form' => $form->createView()
    ]);
  }

  #[Route(
    '/admin/deleted/{id}/image',
    name: 'app_admin_deleted_beer_image',
    requirements: ['id' => '\d+']
  )]
  public function showDeletedBeerImage(
    Beer $beer,
    BeerController $beerController,
    Filesystem $filesystem,
    S3Client $s3client
  ) {
    return $beerController->showImage($beer, $filesystem, $s3client, allowDeleted: true);
  }

  #[Route(
    '/admin/deleted/{id}',
    name: 'app_admin_deleted_beer',
    requirements: ['id' => '\d+']
  )]
  public function showDeletedBeer(
    Beer $beer,
    Request $request,
    EntityManagerInterface $entityManager
  ) {
    $form = $this->createFormBuilder()
      ->add('restore', SubmitType::class, array(
        'label' => 'Restore Deleted Beer',
        'attr' => array(
          'class' => 'btn btn-primary'
        )
      ))
      ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $beer->setDeleted(false);
      $beer->setDeletedReason(null);
      $beer->setModifiedby($this->getUser());
      $beer->setDatemodified(new DateTime());

      $entityManager->persist($beer);
      $entityManager->flush();

      return $this->redirect($this->generateUrl(
        'app_show_beer',
        ["id" => $beer->getId()]
      ));
    }

    return $this->render('admin/deleted_beer.html.twig', array(
      "form" => $form->createView(),
      "beer" => $beer,
    ));
  }

  #[Route('/admin/remove_country', name: 'app_admin_remove_country')]
  public function removeCountry(Request $request, EntityManagerInterface $entityManager)
  {
    $removedCountry = new RemovedCountry();
    $form = $this->createFormBuilder($removedCountry)
      ->add('code', CountryType::class, ['label' => 'Country'])
      ->add('reason', TextType::class)
      ->add('save', SubmitType::class, [
        'label' => 'Remove Country',
        'attr' => [
          'class' => 'btn btn-danger'
        ]
      ])
      ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $removedCountry->setRemovedby($this->getUser());
      $entityManager->persist($removedCountry);
      $entityManager->flush();

      return $this->redirect($this->generateUrl('app_admin'));
    }

    return $this->render('admin/remove_country.html.twig', [
      'form' => $form->createView()
    ]);
  }

  #[Route('/admin/remove_country/{id}/edit', name: 'app_admin_edit_removed_country')]
  public function removedCountry(
    RemovedCountry $removedCountry,
    Request $request,
    EntityManagerInterface $entityManager
  ) {
    /** @var Form $form  */
    $form = $this->createFormBuilder($removedCountry)
      ->add('countryname', TextType::class, [
        'label' => 'Country',
        'disabled' => true
      ])
      ->add('reason', TextType::class)
      ->add('save', SubmitType::class, [
        'label' => 'Save',
        'attr' => ['class' => 'btn btn-primary']
      ])
      ->add('restore', SubmitType::class, [
        'label' => 'Restore',
        'attr' => ['class' => 'btn btn-success']
      ])
      ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      if ($form->has('save') && $form->get('save')->isClicked()) {
        $removedCountry->setModifiedby($this->getUser());
        $removedCountry->setDatemodified(new DateTime());
        $entityManager->persist($removedCountry);
      } else if ($form->has('restore') && $form->get('restore')->isClicked()) {
        $entityManager->remove($removedCountry);
      }
      $entityManager->flush();
      return $this->redirect($this->generateUrl('app_admin'));
    }

    return $this->render('admin/country.html.twig', array(
      "form" => $form->createView()
    ));
  }
}
