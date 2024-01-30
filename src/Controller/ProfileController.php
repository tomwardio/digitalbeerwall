<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileController extends AbstractController
{
  #[Route('/profile', name: 'app_profile')]
  public function showProfile(): Response
  {
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

    return $this->render('profile/show.html.twig', [
      'user' => $this->getUser(),
    ]);
  }

  #[Route('/profile/edit', name: 'app_profile_edit')]
  public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
  {
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

    $user = $this->getUser();

    $form = $this->createFormBuilder($user)
      ->add('username', TextType::class)
      ->add('email', TextType::class)
      ->add('current_password', PasswordType::class, [
        'mapped' => false,
        'constraints' => [new NotBlank(), new UserPassword()]
      ])
      ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $entityManager->persist($user);
      $entityManager->flush();

      return $this->redirect($this->generateUrl('app_profile'));
    }

    return $this->render('profile/edit.html.twig', ['form' => $form]);
  }

  #[Route('/profile/reset_password', name: 'app_profile_reset')]
  public function resetUserPassword(
    ResetPasswordController $resetPasswordController,
    MailerInterface $mailer,
    TranslatorInterface $translator
  ): Response {
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

    return $resetPasswordController->sendPasswordResetEmail(
      $this->getUser(),
      $mailer,
      $translator
    );
  }
}
