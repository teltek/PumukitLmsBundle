# Configuration

```yaml
pumukit_lms:
    enable: true
    password: 'the-secret-shared-with-the-plugins'
    naked_backoffice_domain: 'lms.pumukit.example.org'
    naked_backoffice_background: 'white'
    naked_backoffice_color: '#ED6D00'
    naked_custom_css_url: null
    domains:
        - moodle.example.org
        - '.*\.example\.org'
    default_upload_profile: broadcastable
    lms_upload_url: 'https://lms.pumukit.example.org/moodle/tus'
    lti_learner_profile: null
    allow_create_users_from_request: true
    check_ldap_info_for_permission_profile: true
    role: 'owner'
```

| Option | Default | Description |
|--------|---------|-------------|
| `enable` | `true` | If false, the LMS routes give 404, nothing else of the bundle is loaded (voter, listeners, Twig functions, commands, `frame-ancestors`) and `password` is not required |
| `password` | required if enabled | Secret shared with the LMS plugins to compute the `hash`. The old default `ThisIsASecretPasswordChangeMe` is rejected |
| `naked_backoffice_domain` | `''` | Domain of the naked back-office. Its session cookie is sent with `SameSite=None; Secure` so that it works inside the LMS iframe. The core `pumukit.naked_backoffice_*` parameters must have the same values |
| `naked_backoffice_background`, `naked_backoffice_color`, `naked_custom_css_url` | `white`, `#ED6D00`, `null` | Colors and CSS of the naked back-office |
| `domains` | `[]` | Regular expressions of the hosts of the LMS platforms allowed to log in, embed and register LTI tools; `*` allows any |
| `default_upload_profile` | `broadcastable` | Encoder profile of the uploads: `master_copy`, `master_encoded` or `broadcastable` |
| `lms_upload_url` | `tus` | tus endpoint of the naked back-office wizard, usually `https://{naked domain}/moodle/tus` |
| `lti_learner_profile` | `null` | Permission profile of the LTI learners. If null, the learners watch the embedded videos without becoming users. `Auto Publisher` restores the behaviour of 5.x |
| `allow_create_users_from_request` | `true` | Create the users of the plugins that are not in LDAP |
| `check_ldap_info_for_permission_profile` | `true` | When a `Viewer` user logs in from the LMS, it is promoted to `Auto Publisher` only if the first value of its LDAP attribute `edupersonprimaryaffiliation` is `PAS` or `PDI`. If false, it is promoted without this check. Without the LDAP service, nobody is promoted |
| `role` | `owner` | Role of the people that own the multimedia objects |
