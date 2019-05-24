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
						'name' => trans('Tarrifs'),
						'link' => '?m=etatmssynctariffs',
						'tip' => trans('Assign tvip and lms tarrifs'),
						'prio' => 10,
					),
					array(
						'name' => trans('Configuration'),
						'link' => '?m=etatmssyncconfig',
						'tip' => trans('Tms Sync plugin configurations'),
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

    public function modulesDirInit(array $hook_data = array()) {
		$plugin_modules = EtaTmsSync::$plugindir . DIRECTORY_SEPARATOR .'modules';
        array_unshift($hook_data, $plugin_modules);
        return $hook_data;
	}

    public function smartyInit(Smarty $hook_data) {
        $template_dirs = $hook_data->getTemplateDir();
        $plugin_templates = EtaTmsSync::$plugindir . DIRECTORY_SEPARATOR . 'templates';
        array_unshift($template_dirs, $plugin_templates);
        $hook_data->setTemplateDir($template_dirs);

        $SMARTY = $hook_data;
        return $hook_data;
    }
}
?>
