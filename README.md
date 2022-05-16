# Anonymous Posting

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/clarkwinkelmann/flarum-ext-anonymous-posting/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/clarkwinkelmann/flarum-ext-anonymous-posting.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-anonymous-posting) [![Total Downloads](https://img.shields.io/packagist/dt/clarkwinkelmann/flarum-ext-anonymous-posting.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-anonymous-posting) [![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/clarkwinkelmann)

This extension allows users to create discussions and replies without revealing their usernames except to moderators.

Moderators can also switch existing discussions and posts between anonymous and regular.
The discussion and first post must be updated separately!

Unfortunately the author of the anonymous content will still be rendered as `[deleted]` by Flarum in some places.
You can use the [Prominent Post Numbers](https://github.com/clarkwinkelmann/flarum-ext-prominent-post-numbers) extension to switch some of these texts to the post number instead.

You should not use the [Author Change](https://github.com/clarkwinkelmann/flarum-ext-author-change) extension on an anonymous post, it can lead to unexpected errors.
Instead, you should first de-anonymize the post before changing the author.

## Installation

    composer require clarkwinkelmann/flarum-ext-anonymous-posting

## Support

This extension is under **minimal maintenance**.

It was developed for a client and released as open-source for the benefit of the community.
I might publish simple bugfixes or compatibility updates for free.

You can [contact me](https://clarkwinkelmann.com/flarum) to sponsor additional features or updates.

Support is offered on a "best effort" basis through the Flarum community thread.

## Links

- [GitHub](https://github.com/clarkwinkelmann/flarum-ext-anonymous-posting)
- [Packagist](https://packagist.org/packages/clarkwinkelmann/flarum-ext-anonymous-posting)
