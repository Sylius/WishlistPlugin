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

use Sylius\WishlistPlugin\Processor\SelectedWishlistProductsProcessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final class WishlistCollectionType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SelectedWishlistProductsProcessorInterface $selectedWishlistProductsProcessor,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('items', CollectionType::class, [
                'entry_type' => AddProductsToCartType::class,
                'entry_options' => [
                    'cart' => $options['cart'],
                ],
            ])
            ->add('addAll', SubmitType::class, [
                'label' => 'sylius_wishlist_plugin.ui.add_items_to_cart',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'sylius_wishlist_plugin.ui.save_changes',
            ])
            ->addEventListener(
                FormEvents::SUBMIT,
                $this->pickSelectedWishlistItems(...),
            )
        ;
    }

    public function pickSelectedWishlistItems(FormEvent $event): void
    {
        /** @var FormInterface $addAllButton */
        $addAllButton = $event->getForm()->get('addAll');
        Assert::isInstanceOf($addAllButton, SubmitButton::class);

        /** @var FormInterface $saveButton */
        $saveButton = $event->getForm()->get('save');
        Assert::isInstanceOf($saveButton, SubmitButton::class);

        if ($addAllButton->isClicked() || $saveButton->isClicked()) {
            return;
        }

        $selectedProducts = $this->
        selectedWishlistProductsProcessor->
        createSelectedWishlistProductsCollection(
            $event->getForm()->get('items')->getData(),
        );

        if ($selectedProducts->isEmpty()) {
            $event->getForm()->addError(new FormError($this->translator->trans('sylius_wishlist_plugin.ui.select_products')));
        }

        $event->setData($selectedProducts);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('cart')
            ->setDefault('data_class', null);
    }
}
