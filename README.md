# PumukitLmsBundle

Bundle based on [Symfony](http://symfony.com/) to work with the [PuMuKIT Video Platform](https://github.com/campusdomar/PuMuKIT/blob/2.3.x/README.md).

The goal of this bundle is to merge the common APIs between the [Moodle bundle](https://github.com/teltek/PuMuKIT2-moodle-bundle) and the [OpenEDX bundle](https://github.com/teltek/PuMuKIT2-open-edx-bundle) into single generic bundle.

This code includes:
* An Atto Editor integration for Moodle (/Resources/data/pumoodle/editor/atto/plugins)
* A common API and endpoints that are shared for OpenEDX and Moodle

For the OpenEDX integration, an XBlock is also needed:
- [PuMuKIT2 Opencast Video XBlock](https://github.com/teltek/pumukit2-opencast-video-xblock)

The new Atto Editor integration for Moodle is meant to replace the classical integracion through a mix of repository/filter/block plugins [here](https://github.com/teltek/PuMuKIT2-moodle-bundle)
At the moment, there are a couple of missing features:
* Moodle Playlists support
* Search and publish public videos (published on the WebTV channel)

## Installation steps

### Requirements

Steps 1 and 2 requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 1: Introduce repository in the root project composer.json

Open a command console, enter your project directory and execute the
following command to add this repo:

```bash
$ composer config repositories.pumukitlmsbundle vcs https://github.com/teltek/PuMuKIT2-lms-bundle.git
```

### Step 2: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require teltek/pumukit-lms-bundle dev-master
```

### Step 3: Install the Bundle

Install the bundle by executing the following line command. This command updates the Kernel to enable the bundle (app/AppKernel.php) and loads the routing (app/config/routing.yml) to add the bundle route\
s.

```bash
$ cd /path/to/pumukit/
$ php app/console pumukit:install:bundle Pumukit/LmsBundle/PumukitLmsBundle
```

### Step 4: Configure Bundle

Go to [Resources/doc/Configuration.md](Resources/doc/Configuration.md)

### Step 5: Install Open edX Publication Channel

Install the Open edX Publication Channel with tag code PUCHLMS.

```bash
$ cd /path/to/pumukit/
$ php app/console lms:init:pubchannel
```

### Step 6: Add the PUCHLMS tag code to the desire encoder profiles

Edit your `app/config/encoder.yml` profile to add the PUCHLMS tag code to the desire encoder profiles,
so in case a multimedia object has this Tag, the Tracks with those profiles will be genereated.

For example, if you want to generate a `video_h264` Track each time the Open edX Publication Channel is
added to a Multimedia Object, you should add the tag code this way:

```bash
pumukit_encoder:
    ...
    profiles:
        video_h264:
	    ...
            target: PUCHWEBTV PUCHPODCAST PUCHLMS

```

For PuMuKIT latest version of 2.3.x and 2.4.x, add this configuration to `encoder.yml` as well:

``` bash
pumukit_encoder:
    target_default_profiles:
        PUCHLMS:
            video: "video_h264"
```

### Step 7: (Optional) Set the permissions

Add the "Init Multimedia Objects in published status" role to those users with a
permission profiele with personal scope, if you want them to publish their own
videos immediately. Example for "Auto Publisher" permission profile:

```bash
php app/console pumukit:permission:update "Auto Publisher" ROLE_INIT_STATUS_PUBLISHED
```

### Step 8: Override `PumukitNewAdminBundle:MultimediaObject:list.html.twig` template

Run the lms:init:resources command the branch corresponding to your PuMuKIT version:
e.g If your server has PuMuKIT 2.6.x installed, execute:
```bash
php app/console lms:init:resources 2.6.x
```

If your current version gives an error, please open an issue on Github.

Clear cache:

```bash
php app/console cache:clear && php app/console cache:clear --env=prod
```

## Documentation

1.- [Configuration](Resources/doc/Configuration.md)

2.- [PuMoodle Installation Guide](Resources/doc/PuMoodleInstallationGuide.md)
