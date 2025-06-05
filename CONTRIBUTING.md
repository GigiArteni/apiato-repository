# Contributing to Apiato Repository

Thank you for considering contributing to Apiato Repository! This document outlines the process for contributing to this project.

## Development Setup

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/apiato-repository.git`
3. Install dependencies: `composer install`
4. Run tests: `composer test`

## Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run static analysis
composer analyse
```

## Code Style

This project follows PSR-12 coding standards. You can check your code style with:

```bash
composer format
```

## Pull Request Process

1. Create a feature branch: `git checkout -b feature/your-feature-name`
2. Make your changes
3. Add tests for new functionality
4. Ensure all tests pass
5. Update documentation if needed
6. Submit a pull request

## Reporting Issues

When reporting issues, please include:

- PHP version
- Laravel version
- Apiato version (if applicable)
- Steps to reproduce
- Expected behavior
- Actual behavior

## Feature Requests

Feature requests are welcome! Please:

- Check if the feature already exists
- Describe the use case
- Explain why it would be beneficial
- Consider submitting a pull request

## Code of Conduct

This project follows the [Contributor Covenant](https://www.contributor-covenant.org/) code of conduct.
