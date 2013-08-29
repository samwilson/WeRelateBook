<?php
if (!defined('MEDIAWIKI')) die(0);

/**
 * Extension metadata
 */
$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'WeRelateBook',
    'author' => "Sam Wilson <[mailto:sam@samwilson.id.au sam@samwilson.id.au]>",
    'url' => "http://www.mediawiki.org/wiki/Extension:WeRelate",
    'descriptionmsg' => 'werelatebook-desc',
    'version' => 2.0,
);

/**
 * Messages
 */
$wgExtensionMessagesFiles['WeRelateBook'] = __DIR__ . '/WeRelateBook.i18n.php';

/**
 * Class loading and the Special page
 */
$wgAutoloadClasses['SpecialWeRelateBook'] = __DIR__.'/Special.php';
$wgAutoloadClasses['WeRelateBook_Latex'] = __DIR__.'/Latex.php';
$wgSpecialPages['WeRelateBook'] = 'SpecialWeRelateBook';
