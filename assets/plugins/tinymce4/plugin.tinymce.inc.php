<?php
/* Check plugin.tinymce.php for details */

if (!defined('MODX_BASE_PATH')) { die('What are you doing? Get out of here!'); }

// Init
if( !file_exists(MODX_BASE_PATH."assets/lib/class.modxRTEbridge.php")) { // Add Fall-Back for now
    require_once(MODX_BASE_PATH."assets/plugins/tinymce4/class.modxRTEbridge.php"); 
} else {
    require_once(MODX_BASE_PATH."assets/lib/class.modxRTEbridge.php");
}
require_once(MODX_BASE_PATH."assets/plugins/tinymce4/bridge.tinymce4.inc.php");

$e = &$modx->event;

if($inlineMode == 'enabled' && $e->name == 'OnWebPagePrerender') {
    $options = array('editable'=>array(
        'theme'=>isset($inlineTheme) ? $inlineTheme : 'inline'
    ));
}

$rte = new tinymce4bridge($options);
$rte->setDebug(false);  // true or 'full' for Debug-Infos in HTML-comments

// Internal Stuff - Don´t touch!
$showSettingsInterface = true;  // Show/Hide interface in Modx- / user-configuration
$editorLabel = $rte->pluginParams['editorLabel'];

switch ($e->name) {
    // register for manager
    case "OnRichTextEditorRegister":
        $e->output($editorLabel);
        break;

    // render script for JS-initialization
    case "OnRichTextEditorInit":
        if ($editor === $editorLabel) {
            // Handle introtext-RTE
            if($introtextRte == 'enabled') {
                $rte->pluginParams['elements'][] = 'introtext';
                $rte->tvOptions['introtext']['theme'] = 'introtext';
            }
            $script = $rte->getEditorScript();
            $e->output($script);
        };
        break;

    // Inline-Mode
    case "OnLoadWebPageCache":
    case "OnLoadWebDocument":
        if($inlineMode == 'enabled' && isset($_SESSION['mgrValidated'])) {
            $output = &$modx->documentContent;
            $output = $rte->parseEditableIds($output);
            $rte->protectModxPhs(); // Avoid breaking content / parsing of Modx-placeholders when editing (Inline-Mode)
        }
        break;
    
    case "OnParseDocument":
        if($inlineMode == 'enabled' && isset($_SESSION['mgrValidated'])) {
            $output = &$modx->documentOutput;
            $output = $rte->parseEditableIds($output);
            $rte->protectModxPhs();
        }
        break;

    case "OnWebPagePrerender":
        if($inlineMode == 'enabled' && isset($_SESSION['mgrValidated'])) {
            $rte->set('inline', true, 'bool'); // https://www.tinymce.com/docs/configure/editor-appearance/#inline
            $rte->setPluginParam('elements', 'editable');  // Set missing plugin-parameter manually for Frontend
            $rte->addEditorScriptToBody();
        }
        break;

    // render Modx- / User-configuration settings-list
    case "OnInterfaceSettingsRender":
        if( $showSettingsInterface === true ) {
            $html = $rte->getModxSettings();
            $e->output($html);
        };
        break;

    default :
        return; // important! stop here!
        break;
}