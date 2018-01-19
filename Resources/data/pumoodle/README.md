Installation Guide
==================

The following files are used to install PuMoodle PR into Moodle:
* [install/pumukitpr.zip](install/pumukitpr.zip?raw=true)
* [install/filterpr.zip](install/filterpr.zip?raw=true)

Follow the steps at [PuMoodle Installation Guide](../../doc/PuMoodleInstallationGuide.md).


Admin Guide
===========

If you modify pumukitpr or filter folders,
create the new zip files following these instructions:

```bash
cd ../filter
zip -r ../install/filterpr.zip pumukitpr/
cd ../editor/atto/plugins
zip -r ../../../../install/pumukitpr.zip pumukitpr/
```

Or by executing script:

```bash
./installator.sh
```

NOTE: `/path/to/pumoodle` location:
* In this Bundle: `Resources/data/pumoodle/`
* In a PuMuKIT2 installation: `/path/to/pumukit2/vendor/teltek/pmk2-moodle-bundle/Resources/data/pumoodle/`

