<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Layout\BlockType;
use App\Entity\Security\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * BlockTypeFixtures.
 *
 * BlockType Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockTypeFixtures extends BaseFixtures implements DependentFixtureInterface
{
    private int $position = 1;

    protected function loadData(ObjectManager $manager): void
    {
        $blocks = $this->getContentBlocks();
        foreach ($blocks as $config) {
            $blockType = $this->addBlockType($config);
            $this->addReference($blockType->getSlug(), $blockType);
        }
    }

    /**
     * Generate BlockType.
     */
    private function addBlockType(array $config): BlockType
    {
        /** @var User $user */
        $user = $this->getReference('webmaster', User::class);

        $blockType = new BlockType();
        $blockType->setAdminName($config[0])
            ->setSlug($config[1])
            ->setCategory($config[2])
            ->setIconClass($config[3])
            ->setDropdown(!empty($config[4]))
            ->setEditable(!isset($config[5]))
            ->setPosition($this->position)
            ->setCreatedBy($user);

        if (!empty($config[5])) {
            $blockType->setFieldType(strval($config[5]));
        }

        if (!empty($config[6])) {
            $blockType->setRole($config[6]);
        }

        ++$this->position;
        $this->manager->persist($blockType);
        $this->manager->flush();

        return $blockType;
    }

    /**
     * Get BlockTypes config.
     */
    private function getContentBlocks(): array
    {
        return [
            [$this->translator->trans('Entête', [], 'admin'), 'title-header', 'content', 'fal text-width'],
            [$this->translator->trans('Titre', [], 'admin'), 'title', 'global', 'fal text'],
            [$this->translator->trans('Texte', [], 'admin'), 'text', 'global', 'fal paragraph'],
            [$this->translator->trans('Média', [], 'admin'), 'media', 'global', 'fal image'],
            [$this->translator->trans('Lien', [], 'admin'), 'link', 'global', 'fal link'],
            [$this->translator->trans('Vidéo', [], 'admin'), 'video', 'content', 'fal video'],
            [$this->translator->trans('Mini fiche', [], 'admin'), 'card', 'content', 'fal bookmark', true],
            [$this->translator->trans('Citation', [], 'admin'), 'blockquote', 'content', 'fal quote-right', true],
            [$this->translator->trans('Collapse', [], 'admin'), 'collapse', 'content', 'fal line-height', true],
            [$this->translator->trans('Pop-up', [], 'admin'), 'modal', 'content', 'fal comment-alt', true],
            [$this->translator->trans('Alerte', [], 'admin'), 'alert', 'global', 'fal exclamation-triangle', true],
            [$this->translator->trans('Icône', [], 'admin'), 'icon', 'global', 'fab ravelry', true],
            [$this->translator->trans('Module', [], 'admin'), 'action', 'action', 'fal star', true],
            [$this->translator->trans('Séparateur', [], 'admin'), 'separator', 'global', 'fal grip-lines', true],
            [$this->translator->trans('Widget', [], 'admin'), 'widget', 'content', 'fal code', true],
            [$this->translator->trans('Compteur', [], 'admin'), 'counter', 'global', 'fal sort-numeric-up-alt', true],
            [$this->translator->trans('Boutons de partage', [], 'admin'), 'social-networks', 'global', 'fal share-alt', false, false],
            [$this->translator->trans('Navigation de zones', [], 'admin'), 'zones-navigation', 'global', 'fal bars', false, false],
            [$this->translator->trans('Action', [], 'admin'), 'core-action', 'core', 'fab superpowers'],
        ];
    }

    public function getDependencies(): array
    {
        return [
            SecurityFixtures::class,
        ];
    }
}
