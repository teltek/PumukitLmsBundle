# PumukitOpenEdxBundle

Bundle based on [Symfony](http://symfony.com/) to work with the [PuMuKIT2 Video Platform](https://github.com/campusdomar/PuMuKIT2/blob/2.3.x/README.md).

This bundle adds an API for an Open edX instance to be able to connect to Media Manager. It is intented to work along with:

- [PuMuKIT2 Video Platform version 2.3.x](https://github.com/campusdomar/PuMuKIT2/blob/2.3.x/README.md)
- [Open edX version open-release/ficus](https://github.com/edx/edx-platform)
- [PuMuKIT2 Opencast Video XBlock](https://github.com/teltek/pumukit2-opencast-video-xblock)


## Installation steps

### Requirements

Steps 1 and 2 requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 1: Introduce repository in the root project composer.json

Open a command console, enter your project directory and execute the
following command to add this repo:

```bash
$ composer config repositories.pumukitopenedxbundle vcs https://github.com/teltek/PuMuKIT2-open-edx-bundle.git
```

### Step 2: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require teltek/pmk2-openedx-bundle dev-master
```

### Step 3: Install the Bundle

Install the bundle by executing the following line command. This command updates the Kernel to enable the bundle (app/AppKernel.php) and loads the routing (app/config/routing.yml) to add the bundle route\
s.

```bash
$ cd /path/to/pumukit2/
$ php app/console pumukit:install:bundle Pumukit/OpenEdxBundle/PumukitOpenEdxBundle
```

### Step 4: Configure Bundle

Go to [Resources/doc/Configuration.md](Resources/doc/Configuration.md)

### Step 5: Install Open edX Publication Channel

Install the Open edX Publication Channel with tag code PUCHOPENEDX.

```bash
$ cd /path/to/pumukit2/
$ php app/console openedx:init:pubchannel
```

### Step 6: Add the PUCHOPENEDX tag code to the desire encoder profiles

Edit your `app/config/encoder.yml` profile to add the PUCHOPENEDX tag code to the desire encoder profiles,
so in case a multimedia object has this Tag, the Tracks with those profiles will be genereated.

For example, if you want to generate a `video_h264` Track each time the Open edX Publication Channel is
added to a Multimedia Object, you should add the tag code this way:

```bash
pumukit_encoder:
    ...
    profiles:
        video_h264:
	    ...
            target: PUCHWEBTV PUCHPODCAST PUCHOPENEDX

```

### Step 7: (Optional) Set the permissions

Add the "Init Multimedia Objects in published status" role to those users with a
permission profiele with personal scope, if you want them to publish their own
videos immediately. Example for "Auto Publisher" permission profile:

```bash
php app/console pumukit:permission:update "Auto Publisher" ROLE_INIT_STATUS_PUBLISHED
```

## Documentation

1.- [Configuration](Resources/doc/Configuration.md)

2.- [PuMoodle Installation Guide](Resources/doc/PuMoodleInstallationGuide.md)
