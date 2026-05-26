# Badge expiry tool

## Purpose

This plugin has been created in lieu of [MDL-87808: Send notification when badge expires](https://moodle.atlassian.net/browse/MDL-87808). If this PR gets merged, this plugin will no longer be required.

The general purpose of this plugin is to send notifications to badge recipients when their badge expires.

By default it is not enabled. If you enable it, notifications will only be sent since the time it was enabled (more or less). It will not process historical badges that have expired.

If you want to process historical expired badges, you will need to override the plugin setting "expiredsince" with the timestamp you require (Warning: This could flood emails to users).

## Features not implemented

- **Per badge configuration.** The PR allows instructors to set their own preference for sending notifications with bespoke messages. This can't be implemented here as it requires a change to the badge table or a separately managed table. That's out of scope for something that might be temporary.