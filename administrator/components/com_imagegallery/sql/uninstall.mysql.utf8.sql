DROP TABLE IF EXISTS `#__gallery`;

DELETE FROM `#__content_types` WHERE (type_alias = 'com_imagegallery.galleryimages');