# MIGRATION

How to migration LMSBundle 2.x to 3.x

## [3.0.x](https://github.com/teltek/PumukitLmsBundle/tree/3.0.x)

New features added on LMSBundle 3.0 as added new properties values on MultimediaObjects and Series to identify when a 
video/serie was created from LMS.

To migrate, just execute the following mongodb queries on your PuMuKIT database.

```bash
db.MultimediaObject.update({'properties.openedx' : "1"}, {$set: {'properties.lms' : "1"}}, {multi:true});
```
This mongodb query will add to the new generic property "lms" to all videos with old property "openedx".

```bash
db.MultimediaObject.update({'properties.lms' : "1"}, {$unset: {'properties.openedx' : null}}, {multi:true});
```
This mongodb query will remove old property "openedx".
