<?php
class EtaTmsSyncInitHandler {
    /**
     * Sets plugin managers
     *
     * @param LMS $hook_data Hook data
     */

    private function checkLogDirAccess(){
    }

    public function accessTableInit() {
        $access = AccessRights::getInstance();
        $access->insertPermission(new Permission('etatmssync', trans('EtaTmsSync - Allow access etatmssync plugin '), '^etatmssync$'),
            AccessRights::FIRST_FORBIDDEN_PERMISSION);
    }
}
?>
