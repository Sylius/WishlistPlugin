<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Form\Type;

use Sylius\WishlistPlugin\Entity\Wishlist;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class ImportWishlistFromCsvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('wishlist_file', FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => $options['maxFileSize'],
                        'mimeTypes' => $options['allowedMimeTypes'],
                        'mimeTypesMessage' => 'Please upload a valid CSV file',
                    ]),
                ],
            ])
            ->add('wishlists', EntityType::class, [
                'class' => Wishlist::class,
                'choices' => $options['wishlists'],
                'choice_label' => 'name',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'sylius_wishlist_plugin.ui.submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'maxFileSize' => '512k',
            'allowedMimeTypes' => [
                'text/plain',
                'text/csv',
                'application/csv',
            ],
            'wishlists' => [],
        ]);

        $resolver->setAllowedTypes('maxFileSize', 'string');
        $resolver->setAllowedTypes('allowedMimeTypes', 'array');
        $resolver->setAllowedTypes('wishlists', 'array');
    }
}
