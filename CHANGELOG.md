# Changelog
All notable changes to this project will be documented in this file.

## [1.6] - 2024052100
### Added
- Support for Moodle 4.4
### Fixed
- Coding style fixes
- Failing core unittests, #12

## [1.5] - 2023112400
### Added
- Display course ratings details to non-logged in users instead of redirecting to login page
- Add testing on Moodle 4.3

## [1.4] - 2023072800
### Added
- Compatibility with Moodle 4.1 and 4.2

## [1.3] - 2022111300
### Added
- Course report with all individual ratings and reviews
- For Moodle 4.0 - data source for the report builder for all courses ratings and reviews

### Changed
- Hide "Course rating" field from the course edit form

## [1.2] - 2022070800
### Changed
- Prevent JS from loading in 'embedded' page layout (fixes bug when used with filter_embedquestion plugin).

## [1.1] - 2022070500
### Added
- New setting **tool_courserating/parentcss** allows to specify custom position of the
  course rating on the course page (useful for custom site themes).

## [1.0] - 2022061900
First release
