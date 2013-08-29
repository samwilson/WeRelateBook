<?php
if (!defined('MEDIAWIKI')) die(0);

/**
 * Extension metadata
 */
$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'WeRelateCore',
    'author' => "Sam Wilson <[mailto:sam@samwilson.id.au sam@samwilson.id.au]>",
    'url' => "http://www.mediawiki.org/wiki/Extension:WeRelate",
    'descriptionmsg' => 'werelatecore-desc',
    'version' => 2.0,
);

/**
 * Messages
 */
$wgExtensionMessagesFiles['WeRelateCore'] = __DIR__ . '/WeRelateCore.i18n.php';
$wgExtensionMessagesFiles['WeRelateCoreNamespaces'] = __DIR__ . '/WeRelateCore.namespaces.php';

/**
 * Class loading and the Special page
 */
$wgAutoloadClasses['SpecialWeRelateBook'] = __DIR__.'/Special.php';
$wgAutoloadClasses['WeRelateBook_Latex'] = __DIR__.'/Latex.php';
$wgSpecialPages['WeRelateBook'] = 'SpecialWeRelateBook';
