<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use ReCaptcha\ReCaptcha;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
  private EmailVerifier $emailVerifier;

  public function __construct(EmailVerifier $emailVerifier)
  {
    $this->emailVerifier = $emailVerifier;
  }

  #[Route('/register', name: 'app_register')]
  public function register(
    Request $request,
    UserPasswordHasherInterface $userPasswordHasher,
    EntityManagerInterface $entityManager,
    ReCaptcha $recaptcha,
  ): Response {
    $user = new User();
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {

      // Check recaptcha.
      if ($this->getParameter('google_recaptcha_enabled')) {
        $result = $recaptcha
          ->setExpectedHostname($request->getHost())
          ->verify(
            $request->get('g-recaptcha-response'),
            $request->getClientIp()
          );
        if (!$result->isSuccess()) {
          $form->addError(new FormError(
            'Recaptcha failed! Try again. Errors: ' . implode(
              '',
              $result->getErrorCodes()
            )
          ));
        }
      }

      if ($form->isValid()) {
        // encode the plain password
        $user->setPassword(
          $userPasswordHasher->hashPassword(
            $user,
            $form->get('password')->getData()
          )
        );

        $entityManager->persist($user);
        $entityManager->flush();

        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation(
          'app_verify_email',
          $user,
          (new TemplatedEmail())
            ->from(new Address('noreply@digitalbeerwall.com', 'digitalbeerwall.com'))
            ->to($user->getEmail())
            ->subject('Please Confirm your Email')
            ->htmlTemplate('registration/confirmation_email.html.twig')
        );

        return $this->render('registration/verify.html.twig');
      }
    }

    return $this->render('registration/register.html.twig', [
      'form' => $form->createView(),
      'recaptcha_enabled' => $this->getParameter('google_recaptcha_enabled'),
      'recaptcha_site_key' => $this->getParameter('google_recaptcha_site_key')
    ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
  }

  #[Route('/verify/email', name: 'app_verify_email')]
  public function verifyUserEmail(
    Request $request,
    TranslatorInterface $translator,
    UserRepository $userRepository
  ): Response {
    $id = $request->query->get('id');
    if ($id === null) {
      return $this->redirect('app_login');
    }

    $user = $userRepository->find($id);

    if ($id === null) {
      return $this->redirect('app_login');
    }

    // validate email confirmation link, sets User::isVerified=true and persists
    try {
      $this->emailVerifier->handleEmailConfirmation($request, $user);
    } catch (VerifyEmailExceptionInterface $exception) {
      $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

      return $this->redirectToRoute('app_register');
    }

    $this->addFlash('success', 'Your email address has been verified.');
    return $this->redirectToRoute('app_login');
  }
}
