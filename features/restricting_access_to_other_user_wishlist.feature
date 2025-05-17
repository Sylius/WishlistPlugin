@wishlist
Feature: Restricting access to other's user wishlist
  In order to restrict access to other users wishlists
  As a System
  I want to be able to restrict access to other users wishlists

  Background:
    Given the store operates on a single channel in "United States"
    And there is a customer account "jdeer@sylius.pl" identified by "deer"
    And there is a customer account "jdeer2@sylius.pl" identified by "deer2"
    And user "jdeer@sylius.pl" has a wishlist named "Wishlist1" with token "123456token"
    And user "jdeer2@sylius.pl" has a wishlist named "Wishlist2" with token "123456token"

  @ui
  Scenario: Restricting access to other users wishlist
    When I go to "/"
    And I log in as "jdeer2@sylius.pl" with "deer2" password
    And I go to "/wishlists"
    Then I should have 1 wishlists
    When I try to access "jdeer@sylius.pl" wishlist "Wishlist1"
    Then I should still be on wishlist index page

