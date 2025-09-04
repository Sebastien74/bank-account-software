<?php

declare(strict_types=1);

namespace App\Twig\Core;

use App\Entity\Security\Picture;
use App\Entity\Security\Profile;
use App\Entity\Security\User;
use App\Model\Core\WebsiteModel;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * SecurityRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SecurityRuntime implements RuntimeExtensionInterface
{
    private ?UserInterface $user;

    /**
     * SecurityRuntime constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly string $projectDir,
    ) {
        $this->user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
    }

    /**
     * Get User|UserFront Profile image.
     */
    public function getProfileImg(User|null $user = null): string
    {
        if ($user instanceof User) {
            $picture = $user->getPicture();
            $dirname = $picture instanceof Picture && $picture->getDirname() ? $picture->getDirname() : null;
            $filesystem = new Filesystem();
            if ($dirname && $filesystem->exists($this->projectDir.DIRECTORY_SEPARATOR.'public'.$dirname)) {
                return $dirname;
            }
        }
        $gender = $user && $user->getProfile() && method_exists($user, 'getGender') ? $user->getProfile()->getGender() : null;

        return empty($gender) ? 'medias/anonymous.jpg' : ('mr' === $gender ? 'medias/anonymous-male.jpg' : 'medias/anonymous-female.jpg');
    }

    /**
     * Get User|UserFront Profile Address[].
     */
    public function getProfileAddresses(User|null $user = null): array
    {
        $addresses = [];
        if ($user instanceof User) {
            $profile = $user->getProfile();
            if ($profile instanceof Profile) {
                foreach ($profile->getAddresses() as $address) {
                    $addresses[$address->getSlug()] = $address;
                }
            }
        }

        return $addresses;
    }

    /**
     * Get User|UserFront Profile Address[].
     *
     * @throws Exception
     */
    public function getOnlineUsers(WebsiteModel $website, string $type): array
    {
        $delay = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $delay->setTimestamp(strtotime('2 minutes ago'));

        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->andWhere('u.lastActivity > :delay')
            ->setParameter('delay', $delay);

        return $qb->getQuery()->getResult();
    }

    /**
     * Is granted.
     */
    public function granted(string $roleName): bool
    {
        return $this->user instanceof UserInterface && in_array($roleName, $this->user->getRoles());
    }
}
