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
    public function blockType(): Fixtures\BlockTypeFixtures;
    public function color(): Fixtures\ColorFixtures;
    public function command(): Fixtures\CommandFixtures;
    public function configuration(): Fixtures\ConfigurationFixtures;
    public function defaultMedias(): Fixtures\DefaultMediasFixtures;
    public function layout(): Fixtures\LayoutFixtures;
    public function pageDuplication(): Fixtures\PageDuplicationFixtures;
    public function page(): Fixtures\PageFixtures;
    public function security(): Fixtures\SecurityFixtures;
    public function thumbnail(): Fixtures\ThumbnailFixtures;
    public function transition(): Fixtures\TransitionFixtures;
    public function translations(): Fixtures\TranslationsFixtures;
    public function uploadedFile(): Fixtures\UploadedFileFixtures;
    public function website(): Fixtures\WebsiteFixtures;
}