<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Sonata\UserBundle\Entity\BaseUser as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Assert\NotBlank
     * @Assert\Length(min=3, max=75)
     *
     * @var string
     */
    protected $username;

    /**
     * @Assert\NotBlank
     * @Assert\Email(message="The email {{ value }} is not a valid email.")
     * @Assert\Regex(
     *     pattern="/.+\@wouff\.org$/",
     *     message="The email {{ value }} is not a valid email."
     * )
     *
     * @var string
     */
    protected $email;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @Assert\NotBlank
     * @Assert\Length(min=6, max=75)
     *
     * @var string
     */
    protected $plainPassword;

    public function __construct()
    {
        parent::__construct();
    }
}