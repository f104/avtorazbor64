<?php

/**
 * Проверка корректности кодов в базе
 * Запускается при обновлении каталога или выгрузки.
 * Ошибки пишет в поле error таблицы items
 * 
 * @param int $sklad_id (optional)
 */

namespace Brevis\Components\Hauth;

use Brevis\Components\Component as Component;

class Hauth extends Component {
    
    public $configFile = __DIR__ . '/config.hauth.inc.php';
    private $_config = [];
    
    public $tplPath = 'components/Hauth/';
    public $loginTpl = 'login.tpl';
    public $profileTpl = 'profile.tpl';
    
    /** @var \Hybrid_Auth */
    public $hybridauth;


    public function __construct($core, $config = array()) {
        parent::__construct($core, $config);
        $this->_config = include $this->configFile;
    }
    
    public function initialize() {
        require PROJECT_BASE_PATH . 'vendor/hybridauth/hybridauth/hybridauth/Hybrid/Auth.php';
        $this->hybridauth = new \Hybrid_Auth($this->_config);
    }

    public function getLoginProviders() {
        return array_keys($this->_config['providers']);
    }
    public function getLoginTemplate() {
        return $this->tplPath . $this->loginTpl;
    }
    public function getProfileProviders($user_id) {
        $providers = $this->getLoginProviders();
        $providersForTpl = [];
        foreach ($providers as $provider) {
            $providersForTpl[$provider] = [
                'name' => $provider,
                'active' => 0,
            ];
        }
        if ($services = $this->core->xpdo->getCollection('Brevis\Model\UserHauthService', [
            'user_id' => $user_id,
        ])) {
            foreach ($services as $service) {
                if (isset($providersForTpl[$service->provider])) {
                    $providersForTpl[$service->provider]['active'] = 1;
                }
            }
        }
        return $providersForTpl;
    }
    public function getProfileTemplate() {
        return $this->tplPath . $this->profileTpl;
    }
}