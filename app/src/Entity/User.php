<?php
declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User
 * @author Etienne Dauvergne <contact@ekyna.com>
 */
class User implements UserInterface, EquatableInterface
{
    /** @var string */
    private $password;

    /**
     * Constructor.
     *
     * @param string $password
     */
    public function __construct(string $password = null)
    {
        $this->password = $password;
    }

    public function getRoles()
    {
        return [];
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return null;
    }

    public function eraseCredentials()
    {
        $this->password = null;
    }

    public function isEqualTo(UserInterface $user)
    {
        return $user->getPassword() === $this->password;
    }
}
