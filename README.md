# Anonymous Posting

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/clarkwinkelmann/flarum-ext-anonymous-posting/blob/main/LICENSE.txt) [![Latest Stable Version](https://img.shields.io/packagist/v/clarkwinkelmann/flarum-ext-anonymous-posting.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-anonymous-posting) [![Total Downloads](https://img.shields.io/packagist/dt/clarkwinkelmann/flarum-ext-anonymous-posting.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-anonymous-posting) [![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/clarkwinkelmann)

This extension allows users to create discussions and replies without revealing their usernames except to moderators.

Moderators can also switch existing discussions and posts between anonymous and regular.
The discussion and first post must be updated separately!

Optionally, you can set all new content to be anonymous by default and the permissions will then control who is allowed to post publicly.

Anonymous post authors can still edit their posts like if they were regular posts from them.
Anonymous posts are made visible to moderators and authors on their user profile, but regular users can't see the association.

Unfortunately the author of the anonymous content will still be rendered as `[deleted]` by Flarum in some places.
You can use the [Prominent Post Numbers](https://github.com/clarkwinkelmann/flarum-ext-prominent-post-numbers) extension to switch some of these texts to the post number instead.

Some Flarum notification templates are not able to handle posts without authors and will throw PHP warnings while trying to access properties of `null` objects.
If you hide PHP warnings output in `php.ini` most notifications should continue to send fine without errors and will just show the raw translation placeholder where a display name is supposed to be.

Most notifications should continue working, but the anonymous authors will not get notifications about their anonymous content.
Only reply notifications have been re-implemented to be forwarded to the anonymous author.

You should not use the [Author Change](https://github.com/clarkwinkelmann/flarum-ext-author-change) extension on an anonymous post, it can lead to unexpected errors.
Instead, you should first de-anonymize the post before changing the author.

## Installation

This extension requires PHP 7.4 or higher.

    composer require clarkwinkelmann/flarum-ext-anonymous-posting

## Anonymous Avatars

The anonymous avatars feature allows customizing the avatar of anonymous posts based on attributes of the real author's profile.

This feature requires the premium [Formulaire extension](https://kilowhat.net/flarum/extensions/formulaire) which can be purchased via [Extiverse](https://extiverse.com/extension/kilowhat/flarum-ext-formulaire).

The feature maps Formulaire field values to custom avatar URLs.
If multiple of the conditions match, the first one will be used.

Each condition consists of:

- **Form ID**: the Formulaire profile form ID. You can enter the database ID, the public UID or the public slug. The value will be converted to ID during save.
- **Field Key**: the unique ID of the field inside the form. This value can be found/modified via "Expert Mode" in Formulaire.
- **Field Value**: the value of the field to check against. Exact matches only. For "Date", the format is YYYY-MM-DD. For "Checkboxes", "Radio" and "Dropdown" fields, this is the hidden option ID that can be found/modified via "Expert Mode".
- **Avatar URL**: a value to be applied to the image's `src` attribute. Example: `https://cdn.example.com/image.png` or `/assets/anonymous-avatars/image.png`.

"Upload" and "Multi-select" fields cannot be used in conditions.

In fields that accept multiple answers, each answer will be evaluated separately.
There is no way to check for a combination of answers being selected together.

I recommend setting manual fields and options keys via "Expert Mode" to every field used by this feature, as it makes the settings a lot more readable.
But do not change keys on a form that already has answers!
See [Warnings](https://kilowhat.net/flarum/extensions/formulaire#warnings) in Formulaire documentation.

## Support

This extension is under **minimal maintenance**.

It was developed for a client and released as open-source for the benefit of the community.
I might publish simple bugfixes or compatibility updates for free.

You can [contact me](https://clarkwinkelmann.com/flarum) to sponsor additional features or updates.

Support is offered on a "best effort" basis through the Flarum community thread.

**Sponsors**: [andyli0123](https://andyli.tw/)

## Links

- [GitHub](https://github.com/clarkwinkelmann/flarum-ext-anonymous-posting)
- [Packagist](https://packagist.org/packages/clarkwinkelmann/flarum-ext-anonymous-posting)
- [Discuss](https://discuss.flarum.org/d/31151)
