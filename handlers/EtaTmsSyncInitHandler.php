<?php
class EtaTmsSyncInitHandler {

    private function checkLogDirAccess(){
    }

    public function accessTableInit()
    {
        $access = AccessRights::getInstance();
        if (DBVERSION >= '2020060900') {
            $permission = new Permission(
                'etatmssync_full_access',
                trans('Eta TMS - module management'),
                '^etatmssync.*$',
                null,
                array(
                    'EtaTmsSync' => array(
                    'tariffs',
                    'config')
                )
            );
        } else {
            $permission = new Permission(
                'etatmssync_full_access',
                trans('Eta TMS - module management'),
                '^etatmssync.*$'
            );
        }
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        if (DBVERSION >= '2020060900') {
            $permission = new Permission(
                'etatmssync_device_manager',
                trans('Eta TMS - device manager'),
                '^etatmssync(accountinfo|device).*',
                null,
                null
            );
        } else {
            $permission = new Permission(
                'etatmssync_device_manager',
                trans('Eta TMS - device manager'),
                '^etatmssyncdevice.*'
            );
        }
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        if (DBVERSION >= '2020060900') {
            $permission = new Permission(
                'etatmssync_read_access',
                trans('Eta TMS - information review'),
                '^(etatmssynctariffs|etatmssync.*(info|list)$)',
                null,
                array('EtaTmsSync' => array(
                    "tariffs"
                ))
            );
        } else {
            $permission = new Permission(
                'etatmssync_read_access',
                trans('Eta TMS - information review'),
                '^(etatmssynctariffs|etatmssync.*(info|list)$)'
            );
        }
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);
    }

    public function menuInit($hook_data){
        $menu_tms = array(
            'EtaTmsSync' => array(
                'name' => 'TMS Sync',
                'link' =>'?m=etatmssync',
                'img' => 'etatmssync/icon.webp',
                'accesskey' =>'k',
                'prio' => 15,
                'submenu' => array(
                    "tariffs" => array(
                        'name' => trans('Tarrifs'),
                        'link' => '?m=etatmssynctariffs',
                        'tip' => trans('Assign tvip and lms tarrifs'),
                        'prio' => 10,
                    ),
                    "config" => array(
                        'name' => trans('Configuration'),
                        'link' => '?m=etatmssyncconfig',
                        'tip' => trans('Tms Sync plugin configurations'),
                        'prio' => 20,
                    )
                ),
            )
        );

        $menu_keys = array_keys($hook_data);
        $i = array_search('hosting', $menu_keys);
        return array_slice($hook_data, 0, $i, true) + $menu_tms + array_slice($hook_data, $i, null, true);
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
