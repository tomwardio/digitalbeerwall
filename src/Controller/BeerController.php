<?php

namespace App\Controller;

use App\Entity\Beer;
use App\Repository\BeerRepository;
use Aws\S3\S3Client;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\NotBlank;

class BeerController extends AbstractController
{
  private function saveImage(
    Beer $beer,
    ?string $image,
    Filesystem $filesystem,
    S3Client $s3client
  ) {
    if ($image && strlen($image)) {
      $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));

      $bucket = $this->getParameter('aws_s3_bucket');
      $s3_enabled = $this->getParameter('aws_s3_enabled');

      // Generate unique name, both locally and on S3 (if enabled).
      // This is not foolproof, but it's good enough for this site.
      do {
        $filename = sha1(uniqid(mt_rand(), true)) . '.jpg';
        $local_path = Path::canonicalize(
          $this->getParameter('images_directory') . '/' . $filename
        );
      } while (
        $filesystem->exists($local_path) ||
        ($s3_enabled && $s3client->doesObjectExist($bucket, $filename))
      );

      $filesystem->dumpFile($local_path, $image);

      if ($s3_enabled) {
        $s3client->putObject([
          'Bucket' => $bucket,
          'Key' => $filename,
          'SourceFile' => $local_path,
          'CacheControl' => 'max-age=864000',
        ]);
        $s3client->waitUntil('ObjectExists', [
          'Bucket' => $bucket,
          'Key' => $filename,
        ]);
      }
      $beer->setPath($filename);
      $beer->setBucket($bucket);
    }
  }

  #[Route('/beer/add', name: 'app_add_beer')]
  public function addBeer(
    Request $request,
    Filesystem $filesystem,
    BeerRepository $respository,
    EntityManagerInterface $entityManager,
    S3Client $s3client
  ): Response {
    $beer = new Beer();
    $form = $this->createFormBuilder($beer)
      ->add('imgdata', HiddenType::class, ['mapped' => false])
      ->add('name', TextType::class)
      ->add('country', CountryType::class, ['placeholder' => ''])
      ->add('abv', PercentType::class, ['type' => 'integer', 'scale' => 1])
      ->add('issignature', CheckboxType::class, ['label' => 'Signature', 'required' => false])
      ->add('save', SubmitType::class, [
        'label' => 'Add Beer',
        'attr' => [
          'class' => 'btn btn-primary'
        ]
      ])
      ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->saveImage($beer, $form['imgdata']->getData(), $filesystem, $s3client);
      $beer->setDateAdded(new \DateTime());
      $beer->setDeleted(false);
      $beer->setUser($this->getUser());

      if ($beer->isIssignature()) {
        $old_signature = $respository->findCountrySignature($beer->getCountry());
        if ($old_signature) {
          $old_signature->setIssignature(false);
          $entityManager->persist($old_signature);
        }
      }
      $entityManager->persist($beer);
      $entityManager->flush();
      return $this->redirect($this->generateUrl('app_index'));
    }

    return $this->render(
      'beer/add.html.twig',
      ['form' => $form->createView()]
    );
  }

  #[Route('/beer/{id}', name: 'app_show_beer', requirements: ['id' => '\d+'])]
  public function showBeer(Beer $beer): Response
  {
    if ($beer->isDeleted()) {
      throw new NotFoundHttpException('Beer has been deleted');
    }
    return $this->render('beer/show.html.twig', [
      'beer' => $beer, 'country_names' => Countries::getNames()
    ]);
  }

  #[Route('/beer/{id}/edit', name: 'app_edit_beer', requirements: ['id' => '\d+'])]
  public function editBeer(
    Beer $beer,
    Request $request,
    Filesystem $filesystem,
    S3Client $s3client,
    BeerRepository $respository,
    EntityManagerInterface $entityManager
  ): Response {
    if ($beer->isDeleted()) {
      throw new NotFoundHttpException('Beer has been deleted');
    }

    if ($beer->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
      throw new AccessDeniedException('Not allowed to edit beer if not author or admin.');
    }

    $form = $this->createFormBuilder($beer)
      ->add('imgdata', HiddenType::class, ['mapped' => false])
      ->add('name', TextType::class)
      ->add('country', CountryType::class, ['placeholder' => ''])
      ->add('abv', PercentType::class, ['type' => 'integer', 'scale' => 1])
      ->add('issignature', CheckboxType::class, ['label' => 'Signature', 'required' => false])
      ->add('update', SubmitType::class, [
        'label' => 'Update',
        'attr' => [
          'class' => 'btn btn-primary'
        ]
      ])
      ->getForm();

    $form->handleRequest($request);

    $deleteForm = $this->createFormBuilder($beer)
      ->add('name', HiddenType::class)
      ->add('country', HiddenType::class)
      ->add('abv', HiddenType::class)
      ->add('issignature', HiddenType::class, ['empty_data' => false])
      ->add('deletedReason', TextareaType::class, [
        'label' => 'Reason',
        'required' => true,
        'constraints' => new NotBlank()
      ])
      ->add('delete', SubmitType::class, [
        'label' => 'Delete',
        'attr' => ['class' => 'btn btn-danger']
      ])
      ->getForm();

    $deleteForm->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->saveImage($beer, $form['imgdata']->getData(), $filesystem, $s3client);

      if ($beer->isIssignature()) {
        $old_signature = $respository->findCountrySignature($beer->getCountry());
        if ($old_signature) {
          $old_signature->setIssignature(false);
          $entityManager->persist($old_signature);
        }
      }

      $beer->setModifiedby($this->getUser());
      $beer->setDatemodified(new DateTime());
      $entityManager->persist($beer);
      $entityManager->flush();
      return $this->redirect(
        $this->generateUrl('app_show_beer', ['id' => $beer->getId()])
      );
    } else if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
      $beer->setDeleted(true);
      $beer->setModifiedby($this->getUser());
      $beer->setDatemodified(new DateTime());
      $entityManager->persist($beer);
      $entityManager->flush();

      return $this->redirect($this->generateUrl('app_index'));
    }

    return $this->render('beer/edit.html.twig', [
      'form' => $form->createView(),
      'deleteform' => $deleteForm->createView(),
      'beer' => $beer,
    ]);
  }

  #[Route('/beer/{id}/image', name: 'app_show_beer_image', requirements: ['id' => '\d+'])]
  public function showImage(
    Beer $beer,
    Filesystem $filesystem,
    S3Client $s3client,
    bool $allowDeleted = false,
  ): Response {
    if (!$allowDeleted && $beer->isDeleted()) {
      throw new NotFoundHttpException('Beer has been deleted');
    } else if ($beer->getPath()) {
      $filesystem->mkdir($this->getParameter('images_directory'), 0700);
      $local_path = Path::canonicalize(
        $this->getParameter('images_directory') . '/' . $beer->getPath()
      );

      $s3_enabled = $this->getParameter('aws_s3_enabled');

      if ($filesystem->exists($local_path)) {
        $response = new BinaryFileResponse($local_path);
      } else if ($s3_enabled) {
        try {
          $s3client->getObject([
            'Bucket' => $beer->getBucket(),
            'Key' => $beer->getPath(),
            'SaveAs' => $local_path,
          ]);
        } catch (Exception) {
          $filesystem->remove($local_path);
        }
        $response = new BinaryFileResponse($local_path);
      }

      if ($response) {
        $response->setCache([
          'last_modified' => $beer->getDatemodified(),
          'max_age' => 864000,
          'private' => false,
          'public' => true
        ]);
        return $response;
      } else {
        return $this->createNotFoundException('Cannot find image!');
      }
    }

    return $this->redirect('/default_beer.png');
  }
}
