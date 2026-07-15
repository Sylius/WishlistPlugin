@wishlist
Feature: Browsing the wishlist as a guest
  In order to avoid empty wishlists piling up in the database
  As a Visitor
  I want the wishlist pages to be browsable without a wishlist being created

  Background:
    Given the store operates on a single channel in "United States"

  @ui
  Scenario: Visiting the wishlist page as a guest does not create a wishlist
    Given I am on "/"
    When I go to the wishlist page
    Then I should be on my wishlist page
    And there are 0 wishlists in the database

  @ui
  Scenario: Listing wishlists as a guest without any wishlist does not create a wishlist
    Given I am on "/"
    When I go to "/wishlists"
    Then I should have 0 wishlists
    And there are 0 wishlists in the database
