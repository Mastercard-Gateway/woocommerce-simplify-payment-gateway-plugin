# Changelog
All notable changes to this project will be documented in this file.

## [2.3.1] - 2021-12-01
### Fixed
- Fixed a "false" Transaction Description issue that happened for some types of transactions


## [2.3.0] - 2021-10-19
### Changed
- Add Embedded Payment Option
- Branding Update
- Add One Click Checkout implementation for hosted payment option

### Fixed
- It's impossible to Capture the payment if the Order is Virtual
- It's impossible to Refund the Payment if the plugin works in the Authorization Mode
- Fix the issue with text transformation while save the settings


## [2.2.0] - 2021-01-29

## [2.1.2] - 2021-01-14
### Fixed
- Fixing an issue where performing a partial refund through woocommerce will instead refund the full amount. 


## [2.1.1] 
### Fixed
- Patch to allow for non-latin (e.g. Greek or Arabic) characters in the checkout


## [2.1.0]
### Changed
- Added support for transaction modes, Payment and Authorization
- Added capture and reverse capability


## [2.0.0]
### Changed
- Standard integration has been removed
- Compatibility with Wordpress 5.2 and WooCommerce 3.6


## [1.4.3]
### Fixed
- Fix issue with amounts off by 1c.


## [1.4.2]
### Changed
- Adding Qatar to the supported countries list


## [1.4.1]
### Fixed
- Using hash_equals in return_handler helps prevent timing attacks.


## [1.4.0]
### Changed
- Display supported card types set by merchant


## [1.3.0]
### Changed
- Save hosted payment for subscription


## [1.2.0]
### Changed
- Adding "Australia" to the supported countries list
- Pass currency code in Hosted Payments args


## [1.1.0]
### Fixed
- Fix the card on file not working for subscription payment


## [1.0.0]
### Changed
- First version
