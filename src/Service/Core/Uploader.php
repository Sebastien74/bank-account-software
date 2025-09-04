<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Core\Website;
use App\Service\Content\ImageThumbnailInterface;
use App\Twig\Core\WebsiteRuntime;
use Maestroerror\HeicToJpg;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Uploader.
 *
 * Manage Uploaded File
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class Uploader
{
    private string $uploadsBasePath;
    private ?string $filename = null;
    private ?string $name = null;
    private ?string $extension = null;

    /**
     * Uploader constructor.
     */
    public function __construct(
        private string $uploadsPath,
        private readonly TranslatorInterface $translator,
        private readonly WebsiteRuntime $websiteExtension,
        private readonly ImageThumbnailInterface $imageThumbnail,
    ) {
        $this->uploadsBasePath = $uploadsPath;
        $website = $this->websiteExtension->website();
        if ($website) {
            $this->uploadsPath = $uploadsPath.'/'.$website->uploadDirname;
        }
    }

    /**
     * Upload File.
     */
    public function upload(UploadedFile $uploadedFile, Website $website, ?string $uploadsPath = null, bool $verification = true): bool
    {
        $this->filename($uploadedFile, $website, $verification);

        if (!$uploadedFile->guessExtension()) {
            $message = $this->translator->trans("Une erreur est survenue : L'extension du ficher n'a pas pu être déterminée.", [], 'messages').' <strong>('.$uploadedFile->getClientOriginalName().')</strong>';
            $session = new Session();
            $session->getFlashBag()->add('error', $message);

            return false;
        }

        $uploadDirname = $uploadsPath ?: $this->uploadsBasePath.'/'.$website->getUploadDirname();
        $uploadDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadDirname);
        $uploadedFile->move($uploadDirname, $this->filename);
        $this->checkImage($uploadDirname);

        return true;
    }

    /**
     * Create an UploadedFile object from absolute path.
     */
    public function pathToUploadedFile(string $path, bool $public = true, ?string $tmpDirname = null): ?UploadedFile
    {
        $filesystem = new Filesystem();

        if ($tmpDirname && !$filesystem->exists($tmpDirname)) {
            $filesystem->mkdir($tmpDirname);
        }

        if ($filesystem->exists($path)) {
            $file = new File($path);
            $tmpDirname = $tmpDirname ? $tmpDirname.$file->getFilename() : $this->uploadsBasePath.'/tmp/'.$file->getFilename();

            $filesystem->copy($file->getPathname(), $tmpDirname);

            $tmpFile = new File($tmpDirname);

            return new UploadedFile($tmpFile->getPathname(), $tmpFile->getFilename(), $tmpFile->getMimeType(), null, $public);
        }

        return null;
    }

    /**
     * Get uploads path.
     */
    public function getUploadsPath(): ?string
    {
        return $this->uploadsPath;
    }

    /**
     * Get filename.
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Get name without extension.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get filename.
     */
    public function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * Set filename.
     */
    private function filename(UploadedFile $uploadedFile, Website $website, bool $verification = true): void
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = $verification ? Urlizer::urlize($originalFilename) : Urlizer::urlize($originalFilename).'-'.uniqid();
        $filesystem = new Filesystem();
        $dirname = $this->uploadsBasePath.DIRECTORY_SEPARATOR.$website->getUploadDirname();
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $existingFile = $filesystem->exists($dirname.DIRECTORY_SEPARATOR.$filename.'.'.$uploadedFile->guessClientExtension());
        $this->name = !$existingFile ? $filename : $filename.'-'.uniqid();
        $this->filename = str_contains($uploadedFile->getClientOriginalName(), 'webmanifest') ? $this->name.'.webmanifest'
            : $this->name.'.'.$uploadedFile->guessExtension();
        $this->extension = $uploadedFile->guessExtension();

        if ($existingFile) {
            $session = new Session();
            $session->getFlashBag()->add('warning', $uploadedFile->getClientOriginalName().' '.$this->translator->trans('a été renommé car un fichier du même nom existe déja.', [], 'admin'));
        }
    }

    /**
     * Remove file.
     */
    public function removeFile(?string $filename = null): void
    {
        if ($filename) {
            $dirname = $this->uploadsPath.'/'.$filename;
            $filesystem = new Filesystem();
            if ($filesystem->exists($dirname)) {
                $filesystem->remove($dirname);
            }
        }
    }

    /**
     * Rename file.
     */
    public function rename(string $originalName, string $filename, string $extension): bool
    {
        $originalDirname = $this->uploadsPath.'/'.$originalName.'.'.$extension;
        $dirname = $this->uploadsPath.'/'.Urlizer::urlize($filename).'.'.$extension;
        $filesystem = new Filesystem();
        $existingFile = $filesystem->exists($dirname);
        if ($filename && $originalName && $filesystem->exists($originalDirname) && !$existingFile) {
            $filesystem->rename($originalDirname, $dirname);

            return true;
        }

        return false;
    }

    /**
     * To check image.
     */
    private function checkImage(string $uploadDirname): void
    {
        $this->convert($uploadDirname);
        $uploadDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadDirname).DIRECTORY_SEPARATOR.$this->filename;
        $maxWidth = $this->imageThumbnail->getMaxFileWidth();
        $maxHeight = $this->imageThumbnail->getMaxFileHeight();
        $sizes = getimagesize($uploadDirname);
        $isImage = @is_array($sizes);
        $width = !empty($sizes[0]) ? $sizes[0] : null;
        $height = !empty($sizes[1]) ? $sizes[1] : null;
        $resize = false;
        if ($isImage && $width > $maxWidth) {
            $resize = true;
            $height = ($height * $maxWidth) / $width;
            $width = $maxWidth;
        }
        if ($isImage && $height > $maxHeight) {
            $resize = true;
            $width = ($width * $maxHeight) / $height;
            $height = $maxHeight;
        }

        if ($resize) {
            $width = (int) ceil($width);
            $height = (int) ceil($height);
            $this->resizeImage($uploadDirname, $width, $height);
        }
    }

    /**
     * To convert image if extension is not allowed.
     */
    public function convert(string $uploadDirname): void
    {
        $matches = explode('.', $this->filename);
        $extension = end($matches);
        if ('heic' === $extension) {
            $uploadDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadDirname).DIRECTORY_SEPARATOR;
            $jpgFilename = str_replace('.'.$extension, '.jpg', $this->filename);
            HeicToJpg::convert($uploadDirname.$this->filename)->saveAs($uploadDirname.$jpgFilename);
            $filesystem = new Filesystem();
            if ($filesystem->exists($uploadDirname.$this->filename) && !is_dir($uploadDirname.$this->filename)) {
                $filesystem->remove($uploadDirname.$this->filename);
            }
            $this->filename = $jpgFilename;
            $this->extension = 'jpg';
        }
    }

    /**
     * To resize image if is too large.
     */
    private function resizeImage(string $dirname, int $newWidth, int $newHeight): void
    {
        $imageInfo = getimagesize($dirname);
        $mime = $imageInfo['mime'];

        $sourceImage = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($dirname),
            'image/png' => imagecreatefrompng($dirname),
            'image/gif' => imagecreatefromgif($dirname),
            default => null,
        };

        if ($sourceImage) {
            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);
            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

            switch ($mime) {
                case 'image/jpeg':
                    imagejpeg($newImage, $dirname, 90);
                    break;
                case 'image/png':
                    imagepng($newImage, $dirname);
                    break;
                case 'image/gif':
                    imagegif($newImage, $dirname);
                    break;
            }

            imagedestroy($sourceImage);
            imagedestroy($newImage);

            $session = new Session();
            $session->getFlashBag()->add('warning', $this->translator->trans('Votre image %filename% a été redimensionnée car elle était trop grande.', [
                '%filename%' => $this->filename,
            ], 'admin'));
        }
    }
}
