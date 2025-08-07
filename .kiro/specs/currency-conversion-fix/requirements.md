# Requirements Document

## Introduction

The Angel Marketplace e-commerce platform currently has an issue with currency conversion in the cart and checkout pages. When users change the currency, the subtotal and shipping costs are correctly converted to the selected currency, but the total cost is not consistently updated to match the selected currency. This creates confusion for users and may lead to incorrect pricing information being displayed.

## Requirements

### Requirement 1

**User Story:** As a customer, I want all price displays in the cart and checkout to be consistent with my selected currency, so that I can understand the total cost of my purchase accurately.

#### Acceptance Criteria

1. WHEN a user views the cart page THEN the system SHALL display all prices (item prices, subtotal, shipping, and total) in the same currency.
2. WHEN a user changes the currency on the cart page THEN the system SHALL update all price displays (item prices, subtotal, shipping, and total) to the selected currency.
3. WHEN calculating the total cost THEN the system SHALL use the converted prices in the selected currency for all calculations.
4. WHEN displaying the total cost THEN the system SHALL show the correct currency symbol that matches the selected currency.

### Requirement 2

**User Story:** As a developer, I want to ensure consistent currency formatting across the application, so that users have a seamless experience when switching between currencies.

#### Acceptance Criteria

1. WHEN formatting prices in any currency THEN the system SHALL use the appropriate currency symbol from the settings.
2. WHEN converting between currencies THEN the system SHALL maintain precision and avoid rounding errors.
3. WHEN displaying prices in different currencies THEN the system SHALL use a consistent format (symbol position, decimal places) for each currency.

### Requirement 3

**User Story:** As a store owner, I want to ensure that the currency conversion is accurately reflected in the order records, so that financial records are consistent with what customers see.

#### Acceptance Criteria

1. WHEN an order is created THEN the system SHALL store the currency used for the transaction.
2. WHEN displaying order history THEN the system SHALL show prices in the currency that was used for the transaction.
3. WHEN generating receipts or invoices THEN the system SHALL use the currency that was selected during checkout.

### Requirement 4

**User Story:** As a customer, I want my currency selection to persist from cart to checkout, so that I don't have to reselect the same currency multiple times.

#### Acceptance Criteria

1. WHEN a user selects a currency in the cart page THEN the system SHALL remember this selection when the user proceeds to checkout.
2. WHEN a user navigates from cart to checkout THEN the system SHALL display all prices in the previously selected currency without requiring reselection.
3. WHEN a user returns to the cart after visiting checkout THEN the system SHALL maintain the previously selected currency.