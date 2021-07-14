PuMuKIT Atto plugin for Moodle
==============================

How to generate and update the build for Atto Plugin

## Step 1: Install dependencies for the work.
```
sudo apt-get install npm
sudo apt-get install python-software-properties python g++ make
sudo apt-get update
sudo apt-get install nodejs
sudo ln -s /usr/bin/nodejs /usr/local/bin/node
sudo npm install shifter@0.4.6 -g
```

## Step 2: Go to plugin folder

```
cd {project}/{pathToLMSBundle}/Resources/data/pumoodle/editor/atto/plugins/pumukitpr/src/button
```

## Step 3: Build plugin on code change ( reports errors, recommended for develop )

```
shifter --watch
```

## Step 4: Build to upload code and generate the new version.

```
shifter
```

## Step 5: Install the plugin on Moodle

First generates zip of plugin.
```
docker cp . moodle:/var/www/html/lib/editor/atto/plugins/pumukitpr
zip -r pumukitpr.zip pumukitpr
```

References:

[1] https://docs.moodle.org/dev/YUI/Shifter
[2] https://docs.moodle.org/dev/Atto
[3] https://docs.moodle.org/dev/Grunt
[4] https://github.com/justinhunt/moodle-atto_newtemplate/
