# Changelog

All notable changes to the Hryvinskyi_Csp module will be documented in this file.

## [1.2.1] - 2026-01-30
### Fixed
- Fixed bug in `StoreUrlCollector` that incorrectly set `inlineAllowed=true` for `script-src` policies
- Corrected FetchPolicy constructor parameter comments to match actual parameter names
- The `inline` config setting now correctly controls whether `'unsafe-inline'` appears in CSP headers
- Fixed reports not being deleted when equivalent whitelist entries already exist during mass conversion
- `WhitelistManager::processNewWhitelist()` now calls `deleteRelatedReports()` for all result types

### Added
- Added `RESULT_REDUNDANT` constant to `WhitelistManagerInterface` for entries covered by existing wildcards
- Added duplicate and redundancy checking during CSP report conversion:
  - Detects if a new entry is already covered by an existing wildcard (e.g., `cdn.example.com` when `*.example.com` exists)
  - Detects if a new wildcard is covered by a broader existing wildcard (e.g., `*.sub.example.com` when `*.example.com` exists)
  - Automatically removes existing entries that become redundant when adding a new wildcard
- Separate warning message in admin for reports skipped due to wildcard coverage vs exact duplicates

### Improved
- Enhanced `WhitelistManager` with `DomainMatcherInterface` integration for wildcard matching
- Mass convert now shows distinct counts for: converted, existing duplicates, redundant (wildcard-covered), and errors

## [1.2.0] - 2026-01-30
### Added
- Added CSP value optimization feature to remove duplicate and redundant entries from CSP headers
- New `CspValueOptimizerInterface` and `CspValueOptimizer` for processing CSP header values
- Configuration option to enable/disable CSP value optimization (removes exact duplicates like `data:` appearing twice)
- Configuration option to enable/disable redundant wildcard removal (removes entries covered by wildcards, e.g., `www.example.com` when `*.example.com` exists)
- Automatic detection and warning for unrestricted `*` wildcards in CSP directives
- Logging of optimization results showing bytes saved
- **Redundancy Status Indicator**: New column in whitelist grid showing whether entries are unique, duplicate, or redundant
- **Hash Validation Filtering**: Filter whitelist entries by hash validation status (Valid, Invalid, Not Verified, N/A)
- **Redundancy Status Filtering**: Filter whitelist entries by redundancy status (Unique, Duplicate, Redundant, N/A)
- **Computed Column Sorting**: Sort whitelist grid by Hash Validation and Redundancy Status columns
- New `DomainMatcherInterface` and `DomainMatcher` service for shared domain matching logic
- New `RedundancyCalculatorInterface` and `RedundancyCalculator` for computing entry redundancy
- New `HashValidationCalculatorInterface` and `HashValidationCalculator` for computing hash validation status
- Custom `ListingDataProvider` with PHP-based filtering and sorting for computed columns
- Comprehensive unit tests for all new services

### Improved
- Enhanced `LaminasCspHeaderProcessor` with integrated value optimization before header splitting
- Enhanced `DefaultCspHeaderProcessor` with integrated value optimization support
- CSP headers are now sorted consistently: keywords first, then wildcards, then domains alphabetically
- Refactored `CspValueOptimizer` to use shared `DomainMatcher` service (DRY principle)
- Grid columns now support both filtering and sorting for computed values

### Technical Details
- New admin configuration fields in **Stores > Configuration > Security > Content Security Policy > General**:
  - **Enable CSP value optimization**: Removes duplicate values from CSP directives
  - **Enable redundant wildcard removal**: Removes entries already covered by wildcards (depends on optimization being enabled)
- Optimization runs automatically when enabled, before header splitting (if enabled)
- Debug logging available when debug mode is enabled to track removed redundant entries
- Redundancy detection compares host-type entries within the same policy directive:
  - **Unique**: No other entry with the same value exists
  - **Duplicate**: Exact duplicate of another entry
  - **Redundant**: Covered by a wildcard pattern (e.g., `www.example.com` when `*.example.com` exists)
  - **N/A**: Non-host value types (hash, nonce, keyword)

## [1.1.7] - 2026-01-29
### Fixed
- Fixed TypeError in Import controller: `getValueAlgorithm()` could return null but `getWhitelistByParams()` expected string
- Fixed unique constraint violation during CSV/XML import by preserving `rule_id` when updating existing records
- Added fallback mechanism for constraint violations with direct database lookup to find and update existing records
- Normalized `value_algorithm` to empty string when null/empty to ensure consistent duplicate detection

### Improved
- Enhanced import modal with detailed CSV headers documentation table showing field names, types, and available values
- Added `WhitelistResource` dependency injection for proper database access

## [1.1.4] - 2025-09-18
### Fixed
Reduced column lengths in hryvinskyi_csp_whitelist table to resolve
  MariaDB 3072-byte key limit error:
  - policy: 255→50 characters
  - value_type: 255→50 characters
  - value_algorithm: 50 characters (unchanged)
  - value: 255 characters (unchanged)

  Total unique constraint key size: (50+50+50+255)×4 = 1420 bytes

## [1.1.3] - 2025-07-25
### Added
- Added block-level CSP policy caching system for restoring adding dynamic csp from cached blocks 
- Introduced `BlockCacheDetectorInterface` and `BlockCacheDetector` for detecting cacheable blocks
- Added `BlockCspPolicyHandlerInterface` and `BlockCspPolicyHandler` for managing CSP policies during block rendering
- Implemented `CspPolicyTrackerInterface` and `CspPolicyTracker` for tracking CSP policy changes during block lifecycle
- Created `BlockCspPolicyCacheInterface` and `BlockCspPolicyCache` for caching block-specific CSP policies
- Added `BlockHtmlBeforeObserver` and `BlockHtmlAfterObserver` to handle CSP policies during block rendering events
- Enhanced dependency injection configuration with block-level CSP cache system preferences

### Technical Details
- Block CSP policies are now tracked and cached individually to optimize performance and restoring adding dynamic CSP from cached blocks
- Observers integrate with Magento's block rendering lifecycle (`core_block_abstract_to_html_before` and `core_block_abstract_to_html_after` events)
- New caching layer reduces redundant CSP policy calculations for frequently rendered blocks

## [1.1.2] - 2025-07-24
### Added
- Added `CspNonceProvider` ViewModel class for easy nonce generation in templates
- Added `CspHashGenerator` ViewModel class for generating script hashes and then adding them to the whitelist
- Added `DynamicCspProvider` class for dynamic adding CSP
- Added mass convert functionality for CSP report groups in the admin panel
- Enhanced report group listing UI component with mass convert action button
- Improved cached CSP manager with better policy serialization and caching strategies
- Enhanced CSP hash generator with improved script scanning capabilities
- Extended command-line tools with better error handling and progress reporting

## [1.1.0-alpha] - 2025-05-23
### Added
- Added grouping functionality for CSP reports in the admin panel

## [1.0.10] - 2025-05-07
### Added
- Added possibility to filter whitelist by hash validation status

## [1.0.9.1] - 2025-05-06
### Added
- Introduced `EXCLUDE_SCRIPT_TYPES` configuration for excluding specific script types from hash generation

### Fixed
- Corrected the logic for retrieving website ID from store ID

## [1.0.9] - 2025-05-06
### Added
- Enhanced script hash validation with visual indicators in admin grid. Hash validation indicators (valid, invalid, not verified, not applicable)
- New command-line tool `hryvinskyi:csp:generate-script-hashes` to scan CMS entities for inline scripts
- Support for storing script content alongside hash values for validation
- Automatic hash generation for inline scripts using SHA-256
- Hash validation indicators in the admin UI (valid, invalid, not verified, not applicable)

### Fixed
- Resolved issue with CSP merging

## [1.0.8]
### Added
- Added CSP header splitting functionality to handle large CSP headers
- Added Laminas HTTP integration for proper multi-header handling
- Created new interfaces: CspHeaderProcessorInterface, CspHeaderSplitterInterface, LaminasPluginRegistrarInterface
- Added custom logging capability using hryvinskyi/magento2-logger module
- Added configuration options for CSP header splitting
- Added diagnostics and detailed logging of header splitting operations

### Fixed
- Fixed issue with large CSP headers being truncated or rejected by servers

## [1.0.7]
### Added
- Added support statuses for reports (Pending, Denied, Skipped)
