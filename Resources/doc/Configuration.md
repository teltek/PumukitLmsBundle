# Configuration

```
pumukit_lms:
    password: 'ThisIsASecretPasswordChangeMe'
    role: 'owner'
    naked_backoffice_domain: false
    naked_backoffice_background: 'white'
    naked_backoffice_color: '#ED6D00'
    naked_custom_css_url: null
    default_series_title: 'My LMS Uploads'
    domains:
        - lms.mydomain.com
        - cms.mydomain.com
        - test.moodle.com
```

* `password`: Shared secret between LMS and Pumukit
* `role`: Role used to filter persons in multimedia object
* `naked_backoffice_domain`: Domain or subdomain used to access into the naked backoffice
* `naked_backoffice_background`: CSS color used in the naked backoffice background
* `naked_backoffice_color`: CSS color used in the naked backoffice as main color
* `naked_custom_css_url`: Custom CSS URL
* `default_series_title`: Series title for Multimedia Objects uploaded from LMS
* `domains`: Domains of the platforms connected to this PuMuKIT
