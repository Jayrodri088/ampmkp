# Implementation Plan

- [x] 1. Analyze the current currency conversion implementation
  - Review the existing code in cart.php and checkout.php to identify inconsistencies in currency handling
  - Identify where the currency mismatch occurs in the total calculation
  - _Requirements: 1.1, 1.3_

- [ ] 2. Update the cart.php file to ensure consistent currency display
  - [x] 2.1 Add currency selection functionality to the cart page
    - Implement a dropdown or radio button group for currency selection
    - Add JavaScript to handle currency change events
    - _Requirements: 1.1, 1.2_
  
  - [x] 2.2 Modify the cart total calculation to use the selected currency
    - Update the total calculation to ensure it uses the same currency as subtotal and shipping
    - Ensure the currency symbol is consistent across all price displays
    - _Requirements: 1.3, 1.4, 2.1_

  - [x] 2.3 Add AJAX handler for currency changes in cart page
    - Create an endpoint to recalculate prices when currency changes
    - Update all price displays with the new currency
    - _Requirements: 1.2, 2.3_

- [ ] 3. Implement currency persistence between cart and checkout
  - [ ] 3.1 Create a mechanism to store the selected currency
    - Implement session storage for the selected currency
    - Ensure the currency selection persists across page navigation
    - _Requirements: 4.1, 4.2_
  
  - [ ] 3.2 Update checkout.php to use the persisted currency
    - Remove currency selection from checkout page
    - Apply the persisted currency to all price calculations
    - Verify that the total is correctly calculated from the converted subtotal and shipping
    - _Requirements: 4.2, 1.3, 2.2_
  
  - [ ] 3.3 Update the order creation process to store the selected currency
    - Modify the order data structure to include the currency used
    - Ensure the order total is stored in the selected currency
    - _Requirements: 3.1, 3.2_

- [ ] 4. Implement consistent currency formatting
  - [ ] 4.1 Review and update the currency formatting functions
    - Ensure formatPriceWithCurrency is used consistently
    - Add any missing currency symbols to the settings
    - _Requirements: 2.1, 2.3_
  
  - [ ] 4.2 Add helper function for currency conversion calculations
    - Create a dedicated function for converting prices between currencies
    - Ensure precision is maintained during conversion
    - _Requirements: 2.2_

- [ ] 5. Test the currency conversion functionality
  - [ ] 5.1 Create test cases for different currency scenarios
    - Test switching between all available currencies
    - Verify that all price displays are updated correctly
    - _Requirements: 1.1, 1.2, 1.4_
  
  - [ ] 5.2 Test currency persistence between cart and checkout
    - Verify that currency selection in cart is applied in checkout
    - Test navigation between cart and checkout to ensure persistence
    - _Requirements: 4.1, 4.2, 4.3_
  
  - [ ] 5.3 Test the order creation with different currencies
    - Verify that orders are created with the correct currency
    - Test that order history displays the correct currency
    - _Requirements: 3.2, 3.3_