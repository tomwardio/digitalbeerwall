<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessEventListener implements EventSubscriberInterface
{
  private $em;

  public function __construct(EntityManagerInterface $em = null)
  {
    $this->em = $em;
  }

  public function onLoginSuccess(LoginSuccessEvent $event): void
  {
    $user = $event->getUser();
    if (!$user instanceof User) {
      return;
    }
    $user->setLastLogin(new \DateTime());

    $this->em->persist($user);
    $this->em->flush();
  }

  public static function getSubscribedEvents(): array
  {
    return [
      LoginSuccessEvent::class => 'onLoginSuccess',
    ];
  }
}
