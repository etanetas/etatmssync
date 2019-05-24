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

    public function menuInit($hook_data){
        $menu_gpon = array(
			'EtaTmsSync' => array(
				'name' => 'TMS Sync',
				'link' =>'?m=etatmssync',
				'accesskey' =>'k',
				'prio' => 11,
				'submenu' => array(
					array(
						'name' => trans('Servers'),
						'link' => '?m=etatmssyncservers',
						'tip' => trans('Tms Servers'),
						'prio' => 10,
					),
					array(
						'name' => trans('Tv plans'),
						'link' => '?m=etatmssyncplans',
						'tip' => trans('Assign tvip and lms plans'),
						'prio' => 20,
					),
				),
			),
		);

		$menu_keys = array_keys($hook_data);
		$i = array_search('hosting', $menu_keys);
		array_splice($hook_data, $i + 1, 0, $menu_gpon);
        return $hook_data;  
    }
}
?>
