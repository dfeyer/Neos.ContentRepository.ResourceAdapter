Neos Content Repository Adapter for Asset Management
====================================================

**TLDR**: This package is work in progress and fully experimental

Goals
-----

- Move all assets storage to the content repository
- Make the content repository optional in Media package (by default: Doctrine)
- Provide an upgrade path
- Experiment with adavanced meta data handling
- Experiment with Asset API
- Integrate with Neos CMS
- Improve speed of the CR
    - Filter by property
    - Filter by reference(s)

Roadmap
-------

- [x] Node Type modeling
- [x] Command line utility to support migration (prototype)
    - [x] Tag
    - [x] AssetCollection
    - [x] Document
    - [x] Audio
    - [x] Video
    - [x] Image
    - [x] ImageVariant
    - [x] ResizeImageAdjustment
    - [x] CropImageAdjustment
- [ ] Adapter for the Media package to support doctrine or CR based storage
- [ ] Integrate with Neos
- [ ] Adapt the Media Browser
- [ ] Rock solid Migration tools
- [ ] Asset API
- [ ] Advanced and modular meta data handling
