<?php

if (cfr('ROOT')) {

    set_time_limit(0);

    $messages = new UbillingMessageHelper();
    if (ubRouting::checkGet('checkupdates')) {
        $latestRelease = wr_CheckUpdates(true, 'STABLE');
        die($messages->getStyledMessage($latestRelease, 'success'));
    }

    $updateManager = new UpdateManager();

    if (ubRouting::checkGet($updateManager::ROUTE_AUTOSYSUPGRADE)) {
        $updateBranch = ubRouting::get($updateManager::ROUTE_AUTOSYSUPGRADE);
        $currentSystemVersion = wr_getLocalSystemVersion();
        $updateBranchVersion = wr_GetReleaseInfo($updateBranch);
        if ($currentSystemVersion AND $updateBranchVersion) {
            if ($currentSystemVersion != $updateBranchVersion) {
                
            } else {
                show_success(__('Your software version is already up to date'));
            }
        } else {
            show_error(__('Something went wrong'));
        }
        show_window('', wf_BackLink($updateManager::URL_ME));
    } else {
        if (!ubRouting::checkGet('applysql') AND ! ubRouting::checkGet('showconfigs')) {
            //updates check
            show_window('', $updateManager->renderVersionInfo());

            //available updates lists render
            show_window(__('Database schema updates'), $updateManager->renderSqlDumpsList());
            show_window(__('Configuration files updates'), $updateManager->renderConfigsList());
        } else {
            //mysql dumps applying interface
            if (ubRouting::checkGet('applysql')) {
                show_window(__('MySQL database schema update'), $updateManager->applyMysqlDump(ubRouting::get('applysql')));
            }

            if (ubRouting::checkGet('showconfigs')) {
                show_window(__('Configuration files updates'), $updateManager->applyConfigOptions(ubRouting::get('showconfigs')));
            }
        }
    }
} else {
    show_error(__('Access denied'));
}
        