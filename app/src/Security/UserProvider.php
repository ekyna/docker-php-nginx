<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider
 * @package App\Security
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class UserProvider implements UserProviderInterface
{
    public function loadUserByUsername(string $username)
    {
        if (!isset($_SERVER['AUTH_TOKEN'])) {
            throw new LogicException("AUTH_TOKEN environment variable is not set.");
        }

        return new User($_SERVER['AUTH_TOKEN']);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return new User($_SERVER['AUTH_TOKEN'] ?? null);
    }

    public function supportsClass(string $class)
    {
        return User::class === $class;
    }
}
