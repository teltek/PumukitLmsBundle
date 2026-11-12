# PumukitLmsBundle

Bundle for the [PuMuKIT Video Platform 6.0](https://github.com/pumukit/PuMuKIT/tree/6.0.x) or higher. It connects
PuMuKIT with the LMS platforms:

- the Moodle plugins ([Atto editor](https://github.com/teltek/moodle-atto_pumukitmedia) and
  [filter](https://github.com/teltek/moodle-filter_pumukitmedia)), see [the plugin list](Resources/doc/PLUGIN_LIST.md);
- the [Open edX XBlock](https://github.com/teltek/pumukit2-opencast-video-xblock);
- any LTI 1.3 platform with deep linking, see [LTI](Resources/doc/LTI.md).

The teachers work in the naked back-office (the PuMuKIT back-office without menu, served on its own domain) inside
an iframe of the LMS, and the videos are embedded in the courses with `/openedx/openedx/embed`.

## Installation

```bash
composer require teltek/pumukit-lms-bundle
```

If Symfony Flex does not do it, add the bundle to `config/bundles.php` and import its routes, for example in
`config/routes/pumukit_lms.yaml`:

```php
Pumukit\LmsBundle\PumukitLmsBundle::class => ['all' => true],
```

```yaml
pumukit_lms:
    resource: "@PumukitLmsBundle/Resources/config/routing.yml"
    prefix:   /
```

Configure it as explained in [Resources/doc/Configuration.md](Resources/doc/Configuration.md) and create the LMS
publication channel:

```bash
php bin/console pumukit:lms:init:pubchannel
```

Add `PUCHLMS` to the targets of the encoder profiles that the LMS videos need, for example in
`config/packages/pumukit_encoder.yaml`:

```yaml
pumukit_encoder:
    profiles:
        video_h264:
            target: PUCHWEBTV PUCHPODCAST PUCHLMS
    target_default_profiles:
        PUCHLMS:
            video: "video_h264"
```

To let the users with personal scope publish their videos at once, give them `ROLE_INIT_STATUS_PUBLISHED`:

```bash
php bin/console pumukit:permission:update "Auto Publisher" ROLE_INIT_STATUS_PUBLISHED
```

## Routes

The plugins depend on these routes, which keep their parameters:

| Route | Used by |
|-------|---------|
| `/openedx/sso/manager`, `/openedx/sso/upload`, `/openedx/sso/personal_recorder` | Login of the plugins with `email` or `username` and `hash` |
| `/openedx/openedx/embed/{id}`, `/openedx/openedx/playlist/embed/{id}` | Player of the embedded videos and playlists, with `hash` |
| `/openedx/search/public/multimediaobjects` | Search of public videos; "Publish" sends `postMessage({url})` to the LMS |
| `/lms/embed` | Marks a video as embedded in an LMS |
| `/moodle/tus`, `/moodle/dispatchImport` | Upload of the naked back-office wizard |
| `/lti/register`, `/lti/login`, `/lti/launch`, `/lti/deep_link`, `/.well-known/lti/jwks.json` | LTI 1.3 |
| `/lti/recorder/{token}` | Personal recorder opened from LTI |

The `hash` is `md5(value + password + date(d/m/Y) + naked_backoffice_domain)`, where the value is the email or the
username of the user (or empty for the player), and it is valid the day it is generated and the next one.

## Upload from the naked back-office

The wizard of the naked back-office uploads with tus to `/moodle/tus` on a URL signed for the logged in user, valid
one hour, and imports the file with `/moodle/dispatchImport`, which checks that the same user is logged in. The
user can only upload to their series or their personal series; the global users, to any series.
External callers can still use `/moodle/tus` with `username` or `email` and the `hash`.

## Upgrade from 3.x (PuMuKIT 5)

- The bundle requires PHP 8.5 and PuMuKIT 6.0.
- **`password` is required** when the bundle is enabled, and the old default value is rejected: set the one shared
  with the plugins.
- **Remove the overrides of `templates/bundles/PumukitNewAdminBundle/MultimediaObject/lms_options.html.twig`** and
  the other templates copied by `pumukit:lms:init:resources`, which is removed: PuMuKIT 6.0 has the "Publish to LMS"
  button.
- **LTI:** the launches are validated (issuer and client of the token, signature with the keys of the platform,
  audience, nonce, a signed state and deployment) and only the LTI instructors, content developers and
  administrators are created as PuMuKIT users. The learners watch the embedded videos without becoming users; set
  `lti_learner_profile: 'Auto Publisher'` to create them as before. The launch of an embedded video renews its hash,
  so the videos added with deep linking do not stop working after two days.
- **LTI personal recorder:** the naked back-office opened from LTI shows a "Record" button that opens the personal
  recorder in a new tab, because the LMS iframes do not allow the screen capture. It needs PuMuKIT 6.0 with the
  `pumukit.naked_menu_templates` list.
- **Upload:** the uploads without `hash` are rejected (before, the server computed it, so anybody could upload as any
  user), and the uploads to the series of other users are rejected.
- **Session in the iframe:** the session cookie of the naked domain is sent with `SameSite=None; Secure`, so the
  session works inside the LMS iframe whatever `framework.session.cookie_samesite` says.
- The search page for the LMS and the LTI pages use Bootstrap 5 and no inline scripts. The `/openedx/openedx/embed`
  and `/openedx/openedx/playlist/embed` requests with an unknown or malformed id give 404 instead of 500.
- **Personal series instead of the LMS series:** the uploads and the recordings from the LMS go to the personal
  series of the user, created with the first upload or recording; logging in no longer creates it. The
  `default_series_title` option is removed (delete it from the configuration, it is rejected), the "My LMS Uploads"
  series is no longer used and nobody can upload to it unless they own it. Its videos stay where they are; move them
  to the personal series of their owners with `pumukit:personal-series:move <series id>`.
- New `enable` option: if false, the LMS routes give 404 and nothing else of the bundle is loaded: no voter, listeners, Twig functions, commands or `frame-ancestors`.

See [Resources/doc/MIGRATION.md](Resources/doc/MIGRATION.md) for the upgrade from 2.x.
