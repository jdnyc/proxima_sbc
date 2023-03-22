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
        'common' => CUSTOM_ROOT_WEBPATH . '/javascript/common.js',
        'script' => CUSTOM_ROOT_WEBPATH . '/javascript/script.js',
        // 'Custom.Api' => CUSTOM_ROOT_WEBPATH . '/javascript/api/Custom.Api.js',
        // 'Custom.Store' => CUSTOM_ROOT_WEBPATH . '/javascript/api/Custom.Store.js',
        // 'Custom.ItemListGrid' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ItemListGrid.js',
        // 'Custom.PgmListGrid' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.PgmListGrid.js',
        // 'Custom.UserListGrid' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.UserListGrid.js',
        // 'Custom.PgmSearchForm' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.PgmSearchForm.js',
        // 'Custom.PopupSearchField' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.PopupSearchField.js',
        // 'Custom.ChannelField' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ChannelField.js',
        // 'Custom.HtmlTable' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.HtmlTable.js',
        // 'Custom.ItemSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.ItemSelectWindow.js',
        // 'Custom.PgmSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.PgmSelectWindow.js',
        // 'Custom.PgmItemSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.PgmItemSelectWindow.js',
        // 'Custom.UserSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.UserSelectWindow.js',
        // 'Custom.VideoSelectWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.VideoSelectWindow.js',
        // 'Custom.DetailSearchPanel' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/Custom.DetailSearchPanel.js',
        // 'Custom.ComboTriple' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ComboTriple.js',  
        
        // 'Ext.ux.plugin.FormatTimecode' => '/javascript/ext.ux/Ext.ux.plugin.FormatTimecode.js',

        'Ext.ux.TreeCombo' => '/javascript/ext.ux/Ext.ux.TreeCombo.js',

        'Custom.NullableCombo' => '/custom/ktv-nps/javascript/ext.ux/components/Custom.NullableCombo.js',
        'Custom.SearchCombo' => '/custom/ktv-nps/javascript/ext.ux/components/Custom.SearchCombo.js',
        'Custom.TagLabel' => '/custom/ktv-nps/javascript/ext.ux/components/Custom.TagLabel.js',
        'Custom.TagField' => '/custom/ktv-nps/javascript/ext.ux/components/Custom.TagField.js',
        'Custom.TagPanel' => '/custom/ktv-nps/javascript/ext.ux/components/Custom.TagPanel.js',
        'Custom.TagCombo' => '/custom/ktv-nps/javascript/ext.ux/components/Custom.TagCombo.js',

        'Ariel.IconButton' => '/javascript/component/button/Ariel.IconButton.js',
        'Custom.TreeCombo' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.TreeCombo.js',
        'Custom.TreeCombo2' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.TreeCombo2.js',
        'Custom.CategoryTreeCombo' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.CategoryTreeCombo.js',
        'Custom.CheckboxGroup' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.CheckboxGroup.js',
        'Custom.BISProgramWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.BISProgramWindow.js',
        'Custom.EpisodeListGrid' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.EpisodeListGrid.js',
        'Custom.ProgramListGrid' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ProgramListGrid.js',
        'Custom.ProgramCombo' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ProgramCombo.js',
        'Custom.MaterialSearchField' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.MaterialSearchField.js',
        'Custom.Embargo' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.Embargo.js',
        'Custom.YnPlus' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.YnPlus.js',
        'Custom.ProximaPlayer' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ProximaPlayer.js',
        'Custom.ContentDetailWindow' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ContentDetailWindow.js',
        'Custom.ItemListGrid' => CUSTOM_ROOT_WEBPATH . '/javascript/ext.ux/components/Custom.ItemListGrid.js',   
        'Ariel.System.UserMapWindow' => CUSTOM_ROOT_WEBPATH.'/js/folder/Ariel.System.UserMapWindow.js',
        'Custom.Archive.UrlSet.js' => CUSTOM_ROOT_WEBPATH.'/js/archive/Custom.Archive.UrlSet.js',
        'Custom.Archive.EventWindow.js' => CUSTOM_ROOT_WEBPATH.'/js/archive/Custom.Archive.EventWindow.js',
   
        'Ariel.IngestSchedule' =>  '/javascript/ext.ux/Ariel.IngestSchedule.js',
        'Ariel.MainFunction' => '/javascript/mainFunction.js',
        'Ariel.Nps.DashBoard' => '/javascript/ext.ux/Ariel.Nps.DashBoard.js',
        'Ariel.Das.ArcManage' => '/javascript/ext.ux/Ariel.Das.ArcManage.php',
        'Ariel.task.Monitor' => '/javascript/ext.ux/Ariel.task.Monitor.js', //모니터링,
        'Ariel.offlineTapeLocationList' => '/pages/menu/archive_management/offlineTapeLocationList.js', //
        'Ariel.offlineTapeLogList' => '/pages/menu/archive_management/offlineTapeLogList.js', //
        'Ariel.override' => '/javascript/Ariel.override.js' //마지막     
    ];


    /**
     * 스타일 시트 로드 구문을 반환한다.(html)
     * main.php에서 사용
     *
     * @return array 스타일 로드 구문(html)
     */
    public static function getCustomScripts($loadView = true, $ignoreScriptNames = []): array
    {
        $scripts = [];
        $ver =  config('app')['ver'];

        foreach (self::$scripts as $name => $path) {
            if (in_array($name, $ignoreScriptNames)) {
                continue;
            }
            $path = $path . '?v=' . $ver;
            $script = '<script type="text/javascript" src="' . $path . '"></script>';
            if ($loadView) {
                $script .= "<script>loader_view('{$name}.js');</script>";
            }
            $script .= "\n";
            $scripts[] = $script;
        }

        return $scripts;
    }
}
