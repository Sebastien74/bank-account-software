<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FontawesomeType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FontawesomeType extends AbstractType
{
    private TranslatorInterface $translator;
    private string $projectDir;

    /**
     * FontawesomeType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->projectDir = $this->coreLocator->projectDir();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Icône', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'required' => false,
            'choices' => $this->getIcons(),
            'dropdown_class' => 'icons-selector',
            'attr' => [
                'class' => 'select-icons',
                'group' => 'col-md-4',
            ],
        ]);
    }

    /**
     * Get icons.
     */
    private function getIcons(): array
    {
        $choices = [];
        $filesystem = new Filesystem();
        $assetDirname = '/medias/icons/';
        $dirname = $this->projectDir.'/public'.$assetDirname;
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);

        if ($filesystem->exists($dirname)) {
            $finder = Finder::create();
            $finder->in($dirname);
            $choices[$this->translator->trans('Séléctionnez', [], 'admin')] = '';
            foreach ($finder as $file) {
                if (!empty($file->getRelativePath())) {
                    $path = str_replace(['/', DIRECTORY_SEPARATOR], '\\', $file->getRelativePathname());
                    $matches = explode('\\', $path);
                    $icon = end($matches);
                    $matches = explode('.', $icon);
                    $extension = end($matches);
                    $matches = explode('\\', str_replace('\\'.$icon, '', $path));
                    $category = str_replace(['brands', 'main', 'duotone', 'light', 'regular', 'solid'], ['fab', 'fam', 'fad', 'fal', 'far', 'fas'], end($matches));
                    $icon = $category.' '.str_replace('.'.$extension, '', $icon);
                    $imgSrc = str_replace('\\', '/', $assetDirname.$path);
                    $img = '<img data-src="'.$imgSrc.'" alt="'.$icon.'" height="30" class="lazy-load"/>';
                    $choices[$file->getRelativePath()][$img] = trim($icon);
                }
            }
        }

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
