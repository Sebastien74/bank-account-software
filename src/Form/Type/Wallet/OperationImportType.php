<?php

declare(strict_types=1);

namespace App\Form\Type\Wallet;

use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * OperationImportType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class OperationImportType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * OperationImportType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', Type\FileType::class, [
            'label' => $this->translator->trans('Relevé au format XLSX', [], 'back'),
            'label_html' => true,
            'mapped' => false,
            'attr' => [
                'accept' => '.xlsx',
            ],
            'row_attr' => ['class' => 'col-12'],
            'constraints' => [
                new Assert\NotBlank([
                    'message' => $this->translator->trans('Veuillez sélectionner un fichier à importer.', [], 'back'),
                ]),
                new Assert\File([
                    'maxSize' => '20M',
                    'mimeTypes' => [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/zip',
                        'application/octet-stream',
                    ],
                    'mimeTypesMessage' => $this->translator->trans('Le fichier doit être un classeur Excel au format XLSX.', [], 'back'),
                ]),
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Importer', [], 'back'),
            'attr' => ['class' => 'btn-primary'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'wallet_operation_import';
    }
}
