<?php

namespace ProximaCustom\core;

use ProximaCustom\core\Config;

Config::load();

/**
 * 커스텀 script 로드를 위한 클래스
 */
class ScriptManager
{
    public static $scripts = [
        //'Ext.ux.Review' => '/custom/cjos/javascript/ext.ux/review/Ext.ux.Review.js'
        'common' => CUSTOM_ROOT_WEBPATH . '/javascript/common.js',
        'Custom.Api' => CUSTOM_ROOT_WEBPATH . '/javascript/api/Custom.Api.js',
        'Custom.Store' => CUSTOM_ROOT_WEBPATH . '/javascript/api/Custom.Store.js',
        'Custom.ItemListGrid' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ItemListGrid.js',
        'Custom.PgmListGrid' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.PgmListGrid.js',
        'Custom.UserListGrid' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.UserListGrid.js',
        'Custom.PgmSearchForm' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.PgmSearchForm.js',
        'Custom.PopupSearchField' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.PopupSearchField.js',
        'Custom.ChannelField' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ChannelField.js',
        'Custom.HtmlTable' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.HtmlTable.js',
        'Custom.ItemSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.ItemSelectWindow.js',
        'Custom.PgmSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.PgmSelectWindow.js',
        'Custom.PgmItemSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.PgmItemSelectWindow.js',
        'Custom.UserSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.UserSelectWindow.js',
        'Custom.VideoSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.VideoSelectWindow.js',
        'Custom.DetailSearchPanel' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.DetailSearchPanel.js',
        'Custom.ComboTriple' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ComboTriple.js'
    ];

    /**
     * 스타일 시트 로드 구문을 반환한다.(html)
     * main.php에서 사용
     *
     * @return array 스타일 로드 구문(html)
     */
    public static function getCustomScripts(): array
    {
        $scripts = [];
        foreach (self::$scripts as $name => $path) {
            $scripts[] = '<script type="text/javascript" src="'. $path .'"></script><script>loader_view(\''. $name .'.js\');</script>'."\n";
        }
        return $scripts;
    }
}
