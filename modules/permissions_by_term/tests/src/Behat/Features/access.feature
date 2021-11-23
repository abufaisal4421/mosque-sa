@api @drupal @access
Feature: Access
  Several automated tests for the Permissions by Term Drupal 8 module.

  Background:
    Given I am installing the module named "permissions_by_term"
    Given editor role exists
    Given restricted "tags" terms:
      | name          | access_user   | access_role                             |
      | Tag one       |               | administrator                           |
      | Tag two       |               | authenticated                           |
      | Tag three     |               |                                         |
      | Tag admin     | admin         |                                         |
      | Tag anonymous |               | anonymous, administrator, authenticated |
    Given article content:
      | title                          | author     | status | created           | field_tags    | alias                 |
      | Only admin can access          | Admin      | 1      | 2014-10-17 8:00am | Tag one       | only-admin-can-access |
      | Everybody can access           | Admin      | 1      | 2014-10-17 8:00am |               | no-term               |
      | Term accessible                | Admin      | 1      | 2014-10-17 8:00am | Tag three     | term-no-restriction   |
      | Unpublished node               | Admin      | 0      | 2014-10-17 8:00am |               | unpublished           |
      | Only admin user can edit       | Admin      | 0      | 2014-10-17 8:00am | Tag admin     | unpublished           |
      | Authenticated user can access  | Admin      | 0      | 2014-10-17 8:00am | Tag two       | unpublished           |
      | Anonymous user can access      | Admin      | 1      | 2014-10-17 8:00am | Tag anonymous | anonymous             |
      | Node with tag without perm     | Admin      | 1      | 2014-10-17 8:00am | Tag three     | anonymous             |
    Given page content:
      | title                       | author | status | created           | body      | alias             |
      | Node with no taxonomy field | Admin  | 1      | 2014-10-17 8:00am | Some text | no-taxonomy-field |
    Given users:
      | name          | mail            | pass     |
      | Joe           | joe@example.com | password |
    Given node access records are enabled

  Scenario: Ensure that guest user can access nodes of node type without taxonomy term field
    Given permission mode is not set
    And Node access records are rebuild
    And the cache has been cleared
    And I open the node view by node title "Node with no taxonomy field"
    Then I should not see the text "Access denied"

  Scenario: Anonymous users cannot access taxonomy term view page for "Tag two"
    Given I open taxonomy term view by term name "Tag two"
    Then I should see text matching "Access denied"
    Given I am logged in as a user with the "authenticated" role
    And I open taxonomy term view by term name "Tag two"
    Then I should not see the text "Access denied"

  Scenario: Anonymous users cannot see restricted node
    Given I open the node edit form by node title "Authenticated user can access"
    Then I should not see the text "Authenticated user can access"

  Scenario: Anonymous users can see allowed node with term with multiple user role relation in view
    Given I am on "/"
    And the cache has been cleared
    Then I should see text matching "Anonymous user can access"

  Scenario: Users access nodes by view
    Given I am logged in as a user with the "administrator" role
    Then I am on "/"
    And I should see text matching "Only admin can access"
    Given I am logged in as "Joe"
    Then I am on "/"
    And I should not see the text "Only admin can access"

  Scenario: Editor cannot access disallowed node edit form
    Given I am logged in as a user with the "editor" role
    And I open the node edit form by node title "Only admin can access"
    And I should see text matching "Access denied"
    Then I open the node edit form by node title "Only admin can access"
    And I should see text matching "Access denied"
