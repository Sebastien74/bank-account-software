<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Model\Core\ConfigurationModel;
use Symfony\Component\Asset\Package;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * FontsRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FontsRuntime implements RuntimeExtensionInterface
{
    /**
     * FontsRuntime constructor.
     */
    public function __construct(private readonly Package $assetsManager, private readonly string $projectDir)
    {
    }

    /**
     * Check if as google font.
     */
    public function asGoogleFont(string $template): bool
    {
        $fontsDirname = $this->projectDir.'/assets/scss/front/'.$template.'/';
        $fontsDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fontsDirname);
        $filesystem = new Filesystem();
        if ($filesystem->exists($fontsDirname)) {
            $finder = Finder::create();
            $finder->files()->in($fontsDirname)->name('fonts.scss');
            foreach ($finder as $file) {
                if (str_contains($file->getContents(), 'google')) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get font URL.
     */
    public function fontConfig(string $fontName): ?string
    {
        $fonts['google-barlow'] = 'https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap';
        $fonts['google-bebasneue'] = 'https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap';
        $fonts['google-cabin'] = 'https://fonts.googleapis.com/css2?family=Cabin:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap';
        $fonts['google-catamaran'] = 'https://fonts.googleapis.com/css2?family=Catamaran:wght@100;200;300;400;500;600;700;800;900&display=swap';
        $fonts['google-cormorant-garamond'] = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400;1,500&display=swap';
        $fonts['google-dmsans'] = 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;1,400;1,500;1,700&display=swap';
        $fonts['google-firasans'] = 'https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap';
        $fonts['google-glegoo'] = 'https://fonts.googleapis.com/css2?family=Glegoo:wght@400;700&display=swap';
        $fonts['google-josefin-sans'] = 'https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap';
        $fonts['google-kanit'] = 'https://font.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap';
        $fonts['google-lato'] = 'https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap';
        $fonts['google-montserrat'] = 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap';
        $fonts['google-mplusrounded'] = 'https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@100;300;400;500;700;800;900&display=swap';
        $fonts['google-oldstandard'] = 'https://fonts.googleapis.com/css2?family=Old+Standard+TT:ital,wght@0,400;0,700;1,400&display=swap';
        $fonts['google-opensans'] = 'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap';
        $fonts['google-philosopher'] = 'https://fonts.googleapis.com/css2?family=Philosopher:ital,wght@0,400;0,700;1,400;1,700&display=swap';
        $fonts['google-playfairdisplay'] = 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900&display=swap';
        $fonts['google-poppins'] = 'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap';
        $fonts['google-roboto'] = 'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap';
        $fonts['typekit'] = '';

        $link = !empty($fonts[$fontName]) ? $fonts[$fontName] : '';
        if (!$link) {
            $filesystem = new Filesystem();
            $dirname = $this->projectDir.'/public/build/fonts/font-'.$fontName.'.css';
            $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
            if ($filesystem->exists($dirname)) {
                $link = $this->assetsManager->getUrl('build/fonts/font-'.$fontName.'.css');
            }
        }

        return $link;
    }

    /**
     * Get font family.
     */
    private function fontFamily(string $fontName): ?string
    {
        $fonts['google-barlow'] = "'Barlow', sans-serif";
        $fonts['google-bebasneue'] = "'Bebas Neue', cursive";
        $fonts['google-cabin'] = "'Cabin', sans-serif";
        $fonts['google-catamaran'] = "'Catamaran', sans-serif";
        $fonts['google-cormorant-garamond'] = "'Cormorant Garamond', serif";
        $fonts['google-firasans'] = "'Fira Sans', sans-serif";
        $fonts['google-glegoo'] = "'Glegoo', serif";
        $fonts['google-josefin-sans'] = "'Josefin Sans', sans-serif";
        $fonts['google-kanit'] = "'Kanit', sans-serif";
        $fonts['google-lato'] = "'Lato', sans-serif";
        $fonts['google-montserrat'] = "'Montserrat', sans-serif";
        $fonts['google-mplusrounded'] = "'M PLUS Rounded 1c', sans-serif";
        $fonts['google-oldstandard'] = "'Old Standard TT', serif";
        $fonts['google-opensans'] = "'Open Sans', sans-serif";
        $fonts['google-philosopher'] = "'Philosopher', sans-serif";
        $fonts['google-playfairdisplay'] = "'Playfair Display', sans-serif";
        $fonts['google-poppins'] = "'Poppins', sans-serif";
        $fonts['google-roboto'] = "'Roboto', sans-serif";

        $fontFamily = !empty($fonts[$fontName]) ? $fonts[$fontName] : null;
        if (!$fontFamily) {
            $fontFamily = "'".ucfirst($fontName)."', sans-serif";
        }

        return $fontFamily;
    }

    /**
     * Get front fonts.
     */
    public function appAdminFonts(ConfigurationModel $configuration): string
    {
        $fonts = '';
        $fontsDirname = $this->projectDir.'/assets/scss/front/'.$configuration->template.'/';
        $fontsDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fontsDirname);
        $filesystem = new Filesystem();
        if ($filesystem->exists($fontsDirname)) {
            $finder = Finder::create();
            $finder->files()->in($fontsDirname)->name('fonts.scss');
            foreach ($finder as $file) {
                $pattern = '/\$fontFamily:\s*(.*?);/s';
                preg_match_all($pattern, $file->getContents(), $matches);
                foreach ($matches[1] as $match) {
                    $matchesName = explode(',', $match);
                    $fonts .= ucfirst(str_replace(['"', "'"], '', $matchesName[0])).'='.str_replace(['"', "'"], '', $match).'; ';
                }
            }
        }
        return rtrim($fonts, ', ');
    }
}
