<?php

/**
 * Прием платежей с помощью робокассы
 */

namespace Brevis\Components\Robokassa;

use \xPDO\xPDO as xPDO;
//use Monolog\Logger;
//use Monolog\Handler\StreamHandler;
//use Brevis\Components\MonologPHPMailerHandler\MonologPHPMailerHandler as MonologPHPMailerHandler;
use Brevis\Components\Form\Form as Form;
use Brevis\Controller as Controller;

class Robokassa {
    
    /** var int Id данного типа платежа */
    public $paymentTypeId = 4;
    /** var int Id данного типа платежа */
    public $feeTypeId = 3;
    /** var string Идентификатор магазина в ROBOKASSA */
    public $MerchantLogin = '***';
    /** var string Пароль1 в ROBOKASSA */
    public $Pass1 = '***'; 
    /** var string Пароль2 в ROBOKASSA */
    public $Pass2 = '***';
    /** var string url отправки формы оплаты в ROBOKASSA */
    public $FormUrl = 'https://auth.robokassa.ru/Merchant/Index.aspx';
    /** @var int Количество "дней жизни" неоплаченных заказов */
    public $daysWait = PROJECT_ORDERS_DAY_WAIT;
    /** var float процент агрегатора */
    public $merchantPercent = 0;
    /** var int тестовые платежи, если 1 */
    public $isTest = 0;
    
    public $lang = [];
    public $langTopic = 'payment,fee';

    function __construct($core, $config = []) {
        $this->core = $core;
        $this->controller = new Controller($this->core);
        if (!empty($config)) {
            $this->_readConfig($config);
        }
        $this->_loadConfig();
        if (!empty($this->langTopic)) {
            if (is_string($this->langTopic)) {
                $this->langTopic = explode(',', $this->langTopic);
                $this->langTopic = array_map('trim', $this->langTopic);
            }
            foreach ($this->langTopic as $topic) {
                $this->lang = array_merge($this->lang, $this->controller->loadLexicon($topic));
            }
        }
    }
    
    private function _readConfig(array $config) {
        $properties = get_class_vars(static::class);
        foreach ($properties as $property => $value) {
            if (isset($config[$property])) {
                $this->$property = $config[$property];
            }
        }
    }
    
    private function _loadConfig() {
        $filename = __DIR__ . '/config.inc.php';
        if (file_exists($filename)) {
            include $filename;
            $this->Pass1 = $Pass1;
            $this->Pass2 = $Pass2;
            $this->MerchantLogin = $MerchantLogin;
            if ($this->isTest == 1) {
                $this->Pass1 = $Pass1_test;
                $this->Pass2 = $Pass2_test;
            }
        }
    }

    /**
     * Форма оплаты
     * @param int $InvId Order ID
     * @param string $InvDesc Order description
     * @param float $OutSum Payment sum
     * @param int $UserId User ID
     * @param string $UserEmail user email
     * @return strong
     */
    public function paymentForm($InvId, $InvDesc, $OutSum, $UserId, $UserEmail) {
        $form = new Form([
            'action' => $this->FormUrl,
            'method' => 'post',
            'id' => 'payment_'.$InvId,
            'class' => 'payment-form',
        ], $this->controller);
        $form->hidden('MerchantLogin')->setValue($this->MerchantLogin);
        $form->hidden('InvId')->setValue($InvId);
        $form->hidden('OutSum')->setValue($OutSum);
        $form->hidden('InvDesc')->setValue($InvDesc);
        $form->hidden('Desc')->setValue($InvDesc);
//        $form->hidden('UserId')->setValue($UserId);
        $form->hidden('Email')->setValue($UserEmail);
        $form->hidden('SignatureValue')->setValue($this->_makePaymentCRC($OutSum, $InvId));
        $form->hidden('ExpirationDate')->setValue(date(DATE_ISO8601, time() + $this->daysWait * 86400));
        $form->hidden('isTest')->setValue($this->isTest);
        $form->button('Оплатить', ['type' => 'submit', 'class' => 'btn btn-primary btn-xs']);
        return $form->draw();
    }
    
    /**
     * Форма оплаты
     * @param int $InvId Order ID
     * @param string $InvDesc Order description
     * @param float $OutSum Payment sum
     * @param int $UserId User ID
     * @param string $UserEmail user email
     * @return strong
     */
    public function paymentUrl($OutSum, $UserId, $UserEmail, $Desc) {
          $params = array(
            'MrchLogin' => $this->MerchantLogin,
            'OutSum' => $OutSum,
            'InvId' => '',
            'Email' => $UserEmail,
            'Desc' => $Desc,
            'Shp_uid' => $UserId,
            'isTest' => $this->isTest,
            'SignatureValue' => $this->_makePaymentCRC($OutSum, '', ['Shp_uid' => $UserId]),
          );
          return "https://auth.robokassa.ru/Merchant/Index.aspx?".http_build_query($params);
    }
    
    /**
     * Hash for payment form
     * @param float $OutSum
     * @param int $InvId
     * @return string hash
     */
    private function _makePaymentCRC($OutSum, $InvId, $Shp = null) {
        //MerchantLogin:OutSum:InvId:Пароль#1
        $params = [$this->MerchantLogin, $OutSum, $InvId, $this->Pass1];
        if (!empty($Shp)) {
            ksort($Shp);
            $Shp = http_build_query($Shp,'',':');
            $params[] = $Shp;
        }
//        var_dump(implode(':', $params));die;
        return md5(implode(':', $params));
    }
    
    /**
     * Проверка оплаченного заказа (ResultURL)
     * @param request $request
     * @return bool
     */
    private function _checkPaymentResult($request) {
        if (empty($request['OutSum']) or empty($request['InvId']) or empty($request['SignatureValue'])) {
            $this->error = $this->lang['payment.process_empty_data'];
            return false;
        }
        // проверим заказ
        $order = $this->core->xpdo->getObject('Brevis\Model\Order', ['id' => $fields['order_id']]);
        if (!$order = $this->core->xpdo->getObject('Brevis\Model\Order', $request['InvId'])) {
            $this->error = $this->lang['payment.order_id_incorrect'];
            return false;
        }
        $this->order = $order;
        // проверим статус заказа
        $orderStatus = $order->getOne('Status');
        if ($orderStatus->allow_payment != 1) {
            $this->error = $this->lang['payment.process_status_incorrect'];
            return false;
        }
        // проверим контрольную сумму
        if (strtoupper($request['SignatureValue']) != strtoupper($this->_makePaymentResultCRC($request['OutSum'], $request['InvId']))) {
            $this->error = $this->lang['payment.process_crc_incorrect'];
            return false;
        }
        return true;
    }
    
    /**
     * Процесс проверки оплаченного заказа
     * @param request $request
     * @return string
     */
    public function paymentResult($request) {
        if (empty($request['OutSum']) or empty($request['InvId']) or empty($request['SignatureValue'])) {
            return $this->lang['payment.process_empty_data'];
        }
        // проверим заказ
        if (!$order = $this->core->xpdo->getObject('Brevis\Model\Order', $request['InvId'])) {
            return $this->lang['payment.order_id_incorrect'];
        }
        // проверим статус заказа
        $orderStatus = $order->getOne('Status');
        if ($orderStatus->allow_payment != 1) {
            return $this->lang['payment.process_status_incorrect'];
        }
        // проверим контрольную сумму
        if (strtoupper($request['SignatureValue']) != strtoupper($this->_makePaymentResultCRC($request['OutSum'], $request['InvId']))) {
            return $this->lang['payment.process_crc_incorrect'];
        }
        
        $payment = $this->core->xpdo->newObject('Brevis\Model\Payment', [
            'type_id' => $this->paymentTypeId,
            'order_id' => $order->id,
            'sum' => $request['OutSum'],
        ]);
        $payment->save();
        $order->checkPaymentStatus($this->controller);
        return 'OK'.$order->id;
    }
    
    /**
     * Процесс проверки пополнения баланса
     * @param request $request
     * @return string
     */
    public function rechargeResult($request) {
//        $this->core->logger->error(print_r($request, true));
        if (empty($request['OutSum']) or empty($request['Shp_uid']) or empty($request['SignatureValue'])) {
            return $this->lang['fee.process_empty_data'];
        }
        // проверим пользователя
        if (!$user = $this->core->xpdo->getObject('Brevis\Model\User', $request['Shp_uid'])) {
            return $this->lang['fee.user_id_incorrect'];
        }
        // проверим контрольную сумму
        if (strtoupper($request['SignatureValue']) != strtoupper($this->_makePaymentResultCRC($request['OutSum'], $request['InvId'], ['Shp_uid' => $request['Shp_uid']]))) {
            return $this->lang['payment.process_crc_incorrect'];
        }
        
        // проверим дубликат
        if ($ae = $this->core->xpdo->getObject('Brevis\Model\Fee', ['inv_id' => $request['InvId']])) {
            return $this->lang['fee.ae'];
        }
        
        $sum = round($request['OutSum'] / (1 + $this->merchantPercent / 100));
        
        $fee = $this->core->xpdo->newObject('Brevis\Model\Fee', [
            'type_id' => $this->feeTypeId,
            'user_id' => $user->id,
            'sum' => $sum,
            'comment' => $request['PaymentMethod'],
            'inv_id' => $request['InvId'],
        ]);
        if ($fee->save()) {
            return 'OK'.$request['InvId'];
        } else {
            return 'Save error';
        }
    }

    /**
     * Hash for ResultURL
     * @param float $OutSum
     * @param int $InvId
     * @return string
     */
    private function _makePaymentResultCRC($OutSum, $InvId, $Shp = null) {
        //OutSum:InvId:Пароль#2:Shp
        $params = [$OutSum, $InvId, $this->Pass2];
        if (!empty($Shp)) {
            ksort($Shp);
            $Shp = http_build_query($Shp,'',':');
            $params[] = $Shp;
        }
//        var_dump(implode(':', $params));die;
        return md5(implode(':', $params));
    }
    
}