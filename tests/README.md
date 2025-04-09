# Testing Framework

This directory contains the unified testing framework for the application, replacing the previous scattered test files with a more organized and maintainable approach.

## Structure

The testing framework is divided into two main components:

1. Unit Tests (`UnitTestSuite.php`)
2. End-to-End Tests (`EndToEndTestSuite.php`)

### Unit Tests

The unit tests focus on testing individual components and functions in isolation. They cover:

- Category Management
- Product Management
- Order Processing
- Cart Operations

### End-to-End Tests

The end-to-end tests simulate real user interactions and test complete workflows. They cover:

- User Registration and Authentication
- Product Browsing and Search
- Shopping Cart Operations
- Checkout Process
- Order Tracking

## Running the Tests

To run the tests, use the following commands from the project root:

```bash
# Run unit tests
php tests/UnitTestSuite.php

# Run end-to-end tests
php tests/EndToEndTestSuite.php
```

## Test Results

Test results are displayed in a clear, hierarchical format showing:

- Test group summaries
- Individual test results (✓ for pass, ✗ for fail)
- Overall summary with pass/fail counts
- Details of any failed tests

## Adding New Tests

To add new tests:

1. Identify the appropriate test suite (Unit or E2E)
2. Add a new test method in the relevant section
3. Register the test in the `runAllTests()` method

Example:

```php
private function testNewFeature() {
    $this->runTest('testNewFeature', function() {
        // Test implementation
        return $expectedResult === $actualResult;
    });
}
```

## Best Practices

1. Keep tests focused and isolated
2. Use meaningful test names
3. Clean up test data after tests
4. Document expected behavior
5. Maintain test independence