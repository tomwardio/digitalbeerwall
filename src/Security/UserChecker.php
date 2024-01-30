<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
  public function checkPreAuth(UserInterface $user): void
  {
    if (!$user instanceof AppUser) {
      return;
    }

    if ($user->isLocked()) {
      // the message passed to this exception is meant to be displayed to the user
      throw new CustomUserMessageAccountStatusException('You account is locked.');
    }

    if (!$user->isVerified()) {
      throw new CustomUserMessageAccountStatusException('Account has not been verified!');
    }
  }

  public function checkPostAuth(UserInterface $user): void
  {
  }
}
