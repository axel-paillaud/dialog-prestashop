# AskDialog PrestaShop Module - Development Guide

## Communication Guidelines

- **Response language**: Always respond in French
- **Code & Comments**: 100% in English (code, variables, functions, comments, commit messages)

## Module Overview

AskDialog is a PrestaShop module that integrates conversational AI into e-commerce stores. It consists of two main components:

### 1. Data Export System (Backend → Dialog AI)
- Exports product catalog and CMS pages to Dialog AI platform
- Uses batch processing for large catalogs
- Stores data in JSON format and uploads to S3 via signed URLs
- Manages export queue via `askdialog_product` table

**Key Files:**
- `src/Service/DataGenerator.php`: Generates JSON data for products and CMS pages
- `src/Service/AskDialogClient.php`: Handles API communication with Dialog platform
- `controllers/front/feed.php`: Export endpoint (private API key protected)

### 2. Frontend SDK Integration
- Loads Dialog conversational AI widget on frontend
- Configurable appearance (colors, fonts, borders)
- Can be enabled on product pages with instant questions
- Analytics tracking with PostHog

**Key Files:**
- `views/js/askdialog.js`: Main SDK
- `views/js/ai-input.js`: AI input for general pages
- `views/js/instant.js`: Instant questions on product pages
- `views/js/setupModal.js`: Modal configuration

### 3. Public API
- Provides product and catalog data to Dialog AI
- Supports multilingual and multi-country (with tax calculations)
- Protected by public API key

**Key Files:**
- `controllers/front/api.php`: Public API endpoints

## Current Architecture

### Namespace Structure
- Current: `LouisAuthie\Askdialog`
- Target: `Dialog\AskDialog`

### Data Flow
1. **Export**: PrestaShop → DataGenerator → JSON files → S3 (via AskDialogClient)
2. **Frontend**: User interaction → SDK JS → Dialog AI API
3. **API**: Dialog AI → Public API → Product data

### Database Tables
- `askdialog_product`: Queue for batch export processing

### Configuration (Configuration::get)
- `ASKDIALOG_API_KEY`: Private API key
- `ASKDIALOG_API_KEY_PUBLIC`: Public API key
- `ASKDIALOG_API_URL`: Api URL
- `ASKDIALOG_ENABLE_PRODUCT_HOOK`: Enable on product pages
- `ASKDIALOG_COLOR_PRIMARY`: Primary color
- `ASKDIALOG_COLOR_BACKGROUND`: Background color
- `ASKDIALOG_COLOR_CTA_TEXT`: CTA text color
- `ASKDIALOG_CTA_BORDER_TYPE`: Border type (solid, dashed, etc.)
- `ASKDIALOG_CAPITALIZE_CTAS`: Capitalize CTAs
- `ASKDIALOG_FONT_FAMILY`: Font family
- `ASKDIALOG_HIGHLIGHT_PRODUCT_NAME`: Highlight product name
- `ASKDIALOG_BATCH_SIZE`: Batch size for export (default: 1000000)

### Hooks Used
- `displayHeader`: Load CSS/JS files
- `displayFooterAfter`: Inject Dialog SDK with configuration
- `displayProductAdditionalInfo`: Display assistant on product pages
- `actionFrontControllerInitBefore`: Handle CORS (currently commented)
- `displayOrderConfirmation`: PostHog analytics on order confirmation

## TODO: Current Sprint

### 1. Performance & Architecture
- [ ] **Complete DataGenerator refactoring**: Fix N+1 query problems and improve overall performance
  - Current issue: Multiple database queries for each product (N+1 problem)
  - Goal: Reduce database queries by optimizing data fetching logic

### 2. Data Export Improvements
- [ ] **Generate unique ID names for CMS JSON files**
  - Status: Catalog export already generates unique IDs ✓
  - TODO: Implement same logic for CMS pages export
  
- [ ] **Add import validation**
  - Current issue: Missing validation causes bugs when importing malformed data
  - Goal: Implement validation checks before processing import data

### 3. File System Management
- [ ] **Update CMS JSON file storage path**
  - Replace hardcoded paths with `PathHelper` utility
  - Store files in `var/modules/askdialog/` instead of current location
  - Ensure proper directory creation and permissions 

## Development Workflow

1. **Setup:**
   ```bash
   composer install
   cd views/js/_dev && npm install
   ```

2. **Build assets:**
   ```bash
   cd views/js/_dev && npm run build
   ```

## Code Standards

- Follow PrestaShop coding standards
- Use PSR-12 for PHP code
- Use ESLint for JavaScript
- All classes must have proper PHPDoc
- Use type hints where possible (PHP 7.1+)
- **For all Core PrestaShop classes** (e.g., `Product`, `Context`, `Validation`, `Configuration`, etc.):
  - Always use FQCN (Fully Qualified Class Name) with leading backslash: `\Product`, `\Context`, `\Validation`
  - Never import them with `use` keyword at the top of the file
  - This ensures compatibility across PrestaShop versions and avoids namespace conflicts

## Git Workflow

- Branch naming: `feature/{feature-name}` or `fix/{issue-name}`
- Commit messages: Conventional Commits format (in English)
  - `feature: add new feature`
  - `fix: resolve bug`
  - `refactor: restructure code`
  - `docs: update documentation`
  - `chore: update dependencies`

## Notes

- Module is compatible with PrestaShop 1.6 to 8.x (check `ps_versions_compliancy`)
- Uses Guzzle HTTP client for API calls
- Symfony YAML component for configuration
- Current branch: `feature/remove_1_6_legacy`
