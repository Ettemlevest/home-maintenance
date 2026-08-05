# Requirements

Generally a self-hosted web application that helps to track house chores and related actions with detailed history of them (track who did what, when, how, and with what).

## Concept

Task

Work item
  Type
    Test
    Maintenance
    Repair
    Task
  Requirements
    Tools
    Materials
    Specialist/expert - szakember
  Estimated duration
  Recurrence - mandatory for a maintenance
  Completion criteria - mandatory for a test

## Features, functionality

- one item / product may have multiple tests to be performed with each having their own test step definitions (what to do, expected result)
- maintenance / tests
  - can have multiple steps
  - associated to products
- maintenance / test steps
  - name; detailed instructions; estimated time to perform (prep, doing the test) - only a general categorization is enough like minutes, half hour, hours; required tools, special requirements flagged (e.g. renting is required)
- tests and test steps can have requirements about the results of the test measurements (result should be between a range, value, yes/no, etc)
  - based on the measured values they have a clear result: PASS or FAIL
- products can have different type of associated actions
  - maintenance
  - test
  - repair
  - custom TODO
- tests have customizable recurring configuration (monthly, bi-monthly, every 6 month, yearly, etc)
  - also depending on the previously finished action (only successful ones not counting the failed attempts)
- products, maintenances, tests, repairs can have attached pictures, links to paperless-ngx hosted documents 
- calendar integration (CalDAV?) to show upcoming tasks
  - different type of tasks should have distinctive events in calendar. Different calendars to subscribe? Emoji prefix in the event name?
- HomeAssistant integration possibility
- track car maintenance, repair requirements, also things I cannot do by myself (yearly maintenance check-up at the car service, etc.)

## Use-cases

- Residual Current Device (RCD) (Fí relé / ÁVK) every half a year
- items having a QR coded label - preferably Niimbot D11 compatible label templates and sizes - to quickly identify units
  - scanned in the app leads to its unit details view with testing history, general information, etc
- smoke detectors
  - for example 9 units from 3 different manufacturers, requiring different regular maintenance tasks in different intervals

## Examples from other tools

homer.co
others?

- Google Sheet to track Home Maintenance:
https://docs.google.com/spreadsheets/d/1iyLEX-dkD5UXfjxjkd6z4LJxNstRAX2N/edit?gid=440381970#gid=440381970
from this Reddit thread: https://www.reddit.com/r/homeowners/comments/m7uuy0/for_those_interested_heres_a_home_maintenance/
