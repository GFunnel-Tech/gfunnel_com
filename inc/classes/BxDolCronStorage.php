<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolCronStorage extends BxDolCron
{
    public function processing()
    {
        set_time_limit(36000);
        ignore_user_abort();

        /**
         * if any files were deleted, try to uninstall modules pending for uninstall
         */
        if (BxDolStorage::pruneDeletions())
            BxDolInstallerUtils::checkModulesPendingUninstall();

        /**
         * delete outdated ghosts
         */
        BxDolStorage::pruneGhosts();
    }
}

/** @} */
