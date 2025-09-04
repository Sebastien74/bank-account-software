<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Controller\Admin\AdminController;
use App\Entity\Seo\SeoConfiguration;
use App\Form\Type\Seo\Configuration\ConfigurationType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ConfigurationController.
 *
 * SEO configuration management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/seo/configuration', schemes: '%protocol%')]
class ConfigurationController extends AdminController
{
    protected ?string $class = SeoConfiguration::class;
    protected ?string $formType = ConfigurationType::class;

    /**
     * ConfigurationController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Edit SeoConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/edit', name: 'admin_seoconfiguration_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $website = $this->getWebsite();

        $this->entity = $this->coreLocator->em()->getRepository(SeoConfiguration::class)->findOneByWebsite($website->entity);
        if (!$this->entity) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Cette configuration n\'existe pas !!", [], 'admin'));
        }
        $this->template = 'admin/page/seo/configuration.html.twig';

        return parent::edit($request);
    }
}
