<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Website;
use App\Repository\Core\WebsiteRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ButtonColorType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ButtonColorType extends AbstractType
{
    private const bool CTA = false;
    private const bool CTA_COLORS = true;
    private const bool LINKS_COLORS = true;

    private TranslatorInterface $translator;
    private ?Website $website;
    private array $colors = [];

    /**
     * ButtonColorType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRepository $websiteRepository,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->website = $this->websiteRepository->find($this->coreLocator->requestStack()->getMainRequest()->get('website'));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Style du lien', [], 'admin'),
            'required' => false,
            'cta' => self::CTA,
            'ctaColor' => self::CTA_COLORS,
            'linkColors' => self::LINKS_COLORS,
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => function (OptionsResolver $attr) {
                $attr->setDefaults([
                    'class' => 'select-icons',
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'group' => 'col-md-4',
                    'data-config' => false,
                ]);
            },
            'choice_attr' => function ($color, $key, $value) {
                return [
                    'data-class' => str_contains($color, 'outline') ? 'square-outline' : 'square',
                    'data-color' => $this->colors[$color],
                ];
            },
        ]);

        $resolver->setNormalizer('choices', function (OptionsResolver $options, $value) {
            return $this->getColors($options);
        });
    }

    /**
     * Get WebsiteModel buttons colors.
     */
    private function getColors(OptionsResolver $options): array
    {
        $cta = $options['cta'];
        $ctaColors = $options['ctaColor'];
        $linkColors = $options['linkColors'];
        $colors = $this->website->getConfiguration()->getColors();
        $choices = [];
        $choices[$this->translator->trans('Séléctionnez', [], 'admin')] = '';
        $choices[$this->translator->trans('Lien classique', [], 'admin')] = 'link';
        $this->colors[''] = '';
        $this->colors['link'] = '#ffffff';

        foreach ($colors as $color) {
            if ('button' === $color->getCategory() && $color->isActive()) {
                $choices[$this->translator->trans($color->getAdminName())] = $color->getSlug();
                $this->colors[$color->getSlug()] = $color->getColor();
                if (!str_contains($color->getSlug(), 'outline') && str_contains($color->getSlug(), 'btn')) {
                    $ctaValue = str_replace(['btn'], ['cta'], $color->getSlug());
                    $this->colors[$ctaValue] = $color->getColor();
                    $linkValue = str_replace(['btn'], ['text'], $color->getSlug());
                    $this->colors[$linkValue] = $color->getColor();
                }
            }
        }

        if ($linkColors) {
            foreach ($choices as $label => $value) {
                if (!str_contains($value, 'outline') && preg_match('/btn/', $value)) {
                    $label = str_replace(['Bouton', 'Button'], ['Lien'], $label);
                    $value = str_replace(['btn'], ['text'], $value);
                    $choices[$label] = $value;
                }
            }
        }

        if ($ctaColors) {
            foreach ($choices as $label => $value) {
                if (!str_contains($value, 'outline') && preg_match('/btn/', $value)) {
                    $label = str_replace(['Bouton', 'Button'], ['CTA'], $label);
                    $value = str_replace(['btn'], ['cta'], $value);
                    $choices[$label] = $value;
                }
            }
        } elseif ($cta) {
            $this->colors['cta'] = '';
            $choices[$this->translator->trans('CTA', [], 'admin')] = 'cta';
        }

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
