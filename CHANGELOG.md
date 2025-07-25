# Changelog

All notable changes to the Hryvinskyi_Csp module will be documented in this file.

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