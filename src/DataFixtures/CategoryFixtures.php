<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Wallet\Category;
use App\Entity\Wallet\CategoryType;
use App\Entity\Wallet\SubCategory;
use Doctrine\Persistence\ObjectManager;

/**
 * CategoryFixtures.
 *
 * Category Fixtures management.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryFixtures extends BaseFixtures
{
    private const array CATEGORIES_TYPES = [
        'expenses' => 'Dépenses',
        'incomes' => 'Revenus',
    ];
    private const array CATEGORIES = [
        'expenses' => [
            'other' => 'Autres dépenses',
            'automobile' => 'Frais automobile',
            'life' => 'Frais de vie',
            'various' => 'Frais divers',
            'housing' => 'Frais de logement',
            'taxes' => 'Impôts',
            'leisure-activities' => 'Loisirs',
            'phone' => 'Téléphonie',
        ],
        'incomes' => [
            'other' => 'Autres revenus',
            'refunds' => 'Remboursements',
            'income' => 'Revenus',
        ],
    ];
    private const array SUB_CATEGORIES = [
        'expenses' => [
            'other' => [
                'withdrawals' => 'Retraits',
            ],
            'automobile' => [
                'car-insurance-and-taxes' => 'Assurances et taxes auto',
                'fuel' => 'Carburant',
                'vehicle-maintenance' => 'Entretien du véhicule',
            ],
            'life' => [
                'hairdressers' => 'Coiffeurs',
                'food' => 'Frais alimentaires',
                'medical' => 'Frais Médicaux',
                'clothing' => 'Frais vestimentaires',
            ],
            'various' => [
                'subscriptions' => 'Abonnements',
                'gifts' => 'Cadeaux',
                'travel' => 'Déplacements',
                'supplies' => 'Fournitures',
                'banking' => 'Frais bancaires',
                'tobacco' => 'Tabac',
            ],
            'housing' => [
                'furnishings' => 'Ameublement / Hifi',
                'home-insurance' => 'Assurance logement',
                'do-it-yourself' => 'Bricolage',
                'heating' => 'Chauffage',
                'water-consumption' => 'Consommation eau',
                'electricity-gas' => 'Électricité / Gaz',
                'maintenance-and-services' => 'Entretien et services',
                'rent' => 'Loyer',
            ],
            'taxes' => [
                'income-taxes' => 'Impôts sur les revenus',
                'license-fee' => 'Redevance',
                'residential-tax' => "Taxe d'habitation",
                'property-tax' => 'Taxe foncière',
            ],
            'leisure-activities' => [
                'computer' => 'Informatique',
                'garden' => 'Jardinerie',
                'outings' => 'Sorties',
                'sport' => 'Sport',
                'vacation' => 'Vacances',
            ],
            'phone' => [
                'phone-internet' => 'Téléphone / Internet',
                'internet' => 'Internet',
                'landline-phone' => 'Téléphone fixe',
                'cell-phone' => 'Téléphone mobile',
            ],
        ],
        'incomes' => [
            'other' => [
                'deposits' => 'Dépôts',
                'income-to-be-categorized' => 'Revenus à catégoriser',
            ],
            'refunds' => [
                'miscellaneous-refunds' => 'Remboursements divers',
                'mutual-reimbursements' => 'Remboursements mutuelle',
                'social-security-reimbursements' => 'Remboursements sécu',
            ],
            'income' => [
                'bonuses' => 'Primes',
                'financial-income' => 'Revenus financiers',
                'remuneration' => 'Salaires',
            ],
        ],
    ];

    /**
     * loadData.
     */
    protected function loadData(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $categoryTypePosition = 1;
        foreach (self::CATEGORIES_TYPES as $categoryTypeSlug => $categoryTypeName) {

            $categoryType = new CategoryType();
            $categoryType->setSlug($categoryTypeSlug);
            $categoryType->setAdminName($categoryTypeName);
            $categoryType->setPosition($categoryTypePosition);
            $this->manager->persist($categoryType);
            ++$categoryTypePosition;

            $categoryPosition = 1;
            foreach (self::CATEGORIES[$categoryTypeSlug] as $categorySlug => $categoryName) {

                $category = new Category();
                $category->setSlug($categoryType->getSlug().'-'.$categorySlug);
                $category->setAdminName($categoryName);
                $category->setPosition($categoryPosition);
                $category->setType($categoryType);
                $this->manager->persist($category);
                ++$categoryPosition;

                $subCategoryPosition = 1;
                foreach (self::SUB_CATEGORIES[$categoryTypeSlug][$categorySlug] as $subCategorySlug => $subCategoryName) {
                    $subCategory = new SubCategory();
                    $subCategory->setSlug($category->getSlug().'-'.$subCategorySlug);
                    $subCategory->setAdminName($subCategoryName);
                    $subCategory->setPosition($subCategoryPosition);
                    $subCategory->setCategory($category);
                    $this->manager->persist($subCategory);
                    ++$subCategoryPosition;
                }

            }
        }

        $this->manager->flush();
    }
}