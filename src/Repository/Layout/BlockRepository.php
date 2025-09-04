<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\Block;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BlockRepository.
 *
 * @extends ServiceEntityRepository<Block>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockRepository extends ServiceEntityRepository
{
    /**
     * BlockRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Block::class);
    }

    /**
     * Find by Id.
     *
     * @throws NonUniqueResultException
     */
    public function findById(int $id): ?Block
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.intls', 'i')
            ->andWhere('b.id = :id')
            ->setParameter('id', $id)
            ->addSelect('i')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find Block by titleForce, locale & Page.
     */
    public function findTitleByForceAndLocalePage(mixed $entity, string $locale, ?int $titleForce = null, bool $all = false): array|string|null
    {
        if ($entity->getLayout() instanceof Layout) {
            /** @var array<Block> $results */
            $statement = $this->createQueryBuilder('b')
                ->leftJoin('b.intls', 'i')
                ->leftJoin('b.col', 'c')
                ->leftJoin('c.zone', 'z')
                ->leftJoin('z.layout', 'l')
                ->andWhere('i.titleForce = :titleForce')
                ->andWhere('i.title IS NOT NULL')
                ->andWhere('i.locale = :locale')
                ->andWhere('l.id = :layoutId')
                ->setParameter('titleForce', $titleForce)
                ->setParameter('locale', $locale)
                ->setParameter('layoutId', $entity->getLayout()->getId())
                ->addSelect('i')
                ->addSelect('c')
                ->addSelect('z')
                ->addSelect('l')
                ->addOrderBy('b.position', 'ASC')
                ->addOrderBy('z.position', 'ASC');

            if (!$all) {
                $statement->setMaxResults(1);
            }

            $results = $statement->getQuery()->getResult();

            if ($all) {
                return $results;
            }

            /** @var Block $result */
            $result = $results ? $results[0] : null;

            if ($result instanceof Block) {
                foreach ($result->getIntls() as $intl) {
                    if ($intl->getLocale() === $locale) {
                        return $intl->getTitle();
                    }
                }
            }
        }

        return null;
    }

    /**
     * Find block by titleForce, locale & Layout.
     */
    public function findTitleByForceAndLocaleLayout(mixed $layout, string $locale, int $titleForce, bool $all = false): mixed
    {
        $layoutId = is_array($layout) ? $layout['id'] : $layout->getId();

        $results = $this->createQueryBuilder('b')
            ->leftJoin('b.intls', 'i')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->andWhere('i.titleForce = :titleForce')
            ->andWhere('i.title IS NOT NULL')
            ->andWhere('i.locale = :locale')
            ->andWhere('l.id = :layoutId')
            ->setParameter('titleForce', $titleForce)
            ->setParameter('locale', $locale)
            ->setParameter('layoutId', $layoutId)
            ->addSelect('i')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        /** @var Block $result */
        $result = $results ? $results[0] : null;

        $intlResult = null;
        if (is_object($result) && method_exists($result, 'getIntls')) {
            foreach ($result->getIntls() as $intl) {
                if ($locale === $intl->getLocale()) {
                    $intlResult = $intl;
                    break;
                }
            }
        }

        if ($result && $all) {
            return $intlResult;
        } elseif ($intlResult) {
            return $intlResult->getTitle();
        }

        return null;
    }

    /**
     * Find block by titleForce, locale & Page.
     */
    public function findByBlockTypeAndLocaleLayout(mixed $layout, string $blockType, string $locale, array $options = []): mixed
    {
        $layoutId = is_object($layout) ? $layout->getId() : $layout['id'];
        $asThumb = $options['asThumb'] ?? false;
        $haveContent = $options['haveContent'] ?? false;

        $statement = $this->createQueryBuilder('b')
            ->leftJoin('b.blockType', 'bt')
            ->leftJoin('b.intls', 'i')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->andWhere('bt.slug = :slug')
            ->andWhere('i.locale = :locale')
            ->andWhere('l.id = :layoutId')
            ->setParameter('slug', $blockType)
            ->setParameter('locale', $locale)
            ->setParameter('layoutId', $layoutId)
            ->addSelect('bt')
            ->addSelect('i')
            ->addSelect('c')
            ->addSelect('z')
            ->addSelect('l')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC');

        if ($haveContent && 'title' === $blockType) {
            $statement->andWhere('i.title IS NOT NULL');
        } elseif ($haveContent) {
            $statement->andWhere('i.body IS NOT NULL OR i.introduction IS NOT NULL');
        }

        $blocks = $statement->getQuery()->getResult();

        if ($asThumb) {
            foreach ($blocks as $block) {
                if ($block->isUseForThumb()) {
                    return $block;
                }
            }
            return null;
        }

        return !empty($blocks[0]) ? $blocks[0] : [];
    }

    /**
     * Find block text by locale & Page.
     *
     * @throws NonUniqueResultException
     */
    public function findFieldTextByLocalePage(string $field, Page $page, string $locale): ?Block
    {
        $result = $this->createQueryBuilder('b')
            ->leftJoin('b.blockType', 'bt')
            ->leftJoin('b.intls', 'i')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->leftJoin('l.page', 'p')
            ->andWhere('bt.slug = :slug')
            ->andWhere('i.'.$field.' IS NOT NULL')
            ->andWhere('i.locale = :locale')
            ->andWhere('p.id = :page')
            ->setParameter('slug', 'media')
            ->setParameter('locale', $locale)
            ->setParameter('page', $page)
            ->addSelect('bt')
            ->addSelect('i')
            ->addSelect('l')
            ->addSelect('z')
            ->addSelect('c')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $getter = 'get'.ucfirst($field);

        return $result ? $result->getIntls()[0]->$getter() : null;
    }

    /**
     * Find block text by locale & Page.
     */
    public function findMediaByLocalePage(Page $page, string $locale): array
    {
        $result = $this->createQueryBuilder('b')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->leftJoin('l.page', 'p')
            ->andWhere('m.filename IS NOT NULL')
            ->andWhere('m.screen = :screen')
            ->andWhere('mr.locale = :locale')
            ->andWhere('p.id = :page')
            ->setParameter('locale', $locale)
            ->setParameter('page', $page)
            ->setParameter('screen', 'desktop')
            ->addSelect('l')
            ->addSelect('z')
            ->addSelect('c')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($result[0]['mediaRelations'][0]['media']) ? $result[0]['mediaRelations'][0]['media'] : [];
    }

    /**
     * Find by Action.
     */
    public function findByAction(string $classname, int $filterId): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.intls', 'bi')
            ->leftJoin('b.action', 'a')
            ->leftJoin('b.actionIntls', 'ai')
            ->andWhere('a.entity = :entity')
            ->andWhere('ai.actionFilter = :actionFilter')
            ->setParameter('entity', $classname)
            ->setParameter('actionFilter', $filterId)
            ->getQuery()
            ->getResult();
    }
}
