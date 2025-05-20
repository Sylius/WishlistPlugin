import './handleWishlistMainCheckbox';
import './handleCopyToWishlistListModal';
import './handleAddAnotherWishlistModal';
import './handleRemoveWishlistModal';
import './handleEditWishlistModal';
import { WishlistVariantButton } from './WishlistVariantButton';

const WishlistVariantElements = [...document.querySelectorAll('[data-bb-toggle="wishlist-variant"]')];
export const WishlistVariantButtonList = WishlistVariantElements.map(button => new WishlistVariantButton(button).init());

export default {
    WishlistVariantButtonList
};
