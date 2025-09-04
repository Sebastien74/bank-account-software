<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\DataFixtures as Fixtures;

/**
 * DataFixturesInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface DataFixturesInterface
{
    public function configuration(): Fixtures\ConfigurationFixtures;
    public function security(): Fixtures\SecurityFixtures;
    public function translations(): Fixtures\TranslationsFixtures;
    public function website(): Fixtures\WebsiteFixtures;
}