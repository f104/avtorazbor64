<?php

    namespace Brevis\Model;

    use xPDO\xPDO;

    class Order extends \xPDO\Om\xPDOSimpleObject {
        
        public $controller = null;
        /** @var int id типа платежа для списания с баланса */
        public $withdrawType = 4;
        /** @var int id типа платежа для возврата на баланса */
        public $refundType = 5;
        /** @var int id оплаченного статуса заказа */
        public $paidStatus = 6;


        public function remove(array $ancestors = array()) {
            if ($item = $this->getOne('Item')) {
                $item->set('reserved', 0);
                $item->save();
            }
            return parent::remove($ancestors);
        }
        
        public function set($k, $v = null, $vType = '') {
            switch ($k) {
                case 'status_id':
                    switch ($v) {
                        case 2:
                        case 13:
                        case 14:
                            // отменен
                            if ($item = $this->getOne('Item')) {
                                $item->set('reserved', 0);
                                $item->save();
                            }
                            break;
                    }
                    break;
            }
            return parent::set($k, $v, $vType);
        }
        
        public function checkPaymentStatus($controller) {
//            if ($this->status_id < 4 or $this->status_id > 6) { return; } // 4 - ожидает оплаты, 6 - оплачен
            $newStatus = null;
            $c = $this->xpdo->newQuery('Brevis\Model\Payment');
            $c->where(['order_id' => $this->id]);
            $c->select('SUM(`sum`)');
            if ($c->prepare()) {
                $sumTotal = $this->xpdo->getValue($c->stmt);
                if ($sumTotal !== NULL) {
                    if ($sumTotal >= $this->cost) {
                        $newStatus = 6; // отплачен
                    } elseif ($sumTotal > 0) {
                        $newStatus = 5; // частично оплачен
                    } else {
                        $newStatus = 4; // ожидает оплаты
                    }
                }
            }
            if (!empty($newStatus) and $newStatus != $this->status_id) {
                $this->controller = $controller;
                $this->set('status_id', $newStatus);
                if ($this->save()) {
                    $this->notifyBuyer();
                    if ($newStatus == 6) {
                        $this->_notifySupplierPaid();
                    }
                }
            }
        }
        
        /**
         * Списывает с баланса оплату по заказу
         * @return false если ошибка
         * @return Brevis\Model\Status статус "оплачен"
         */
        public function runWithdraw() {
            if (!$this->is_paid and $user = $this->getOne('User')) {
                if ($user->balance >= $this->cost) {
                    $fee = $this->xpdo->newObject('Brevis\Model\Fee', [
                        'type_id' => $this->withdrawType,
                        'sum' => -$this->cost,
                        'user_id' => $user->id,
                        'order_id' => $this->id,
                    ]);
                    if ($fee->save() !== false) { // почему-то при успехе возвращает null
                        // ставим статус "оплачен"
                        $status = $this->xpdo->getObject('Brevis\Model\OrderStatus', ['id' => $this->paidStatus]);
                        $this->set('status_id', $status->id);
                        $this->set('is_paid', 1);
                        $this->save();
                        return $status;
                    }
                }
            }
            return false;
        }
        
        /**
         * Возврат средств по данному заказу
         * @param string $comment Комментарий
         */
        public function refundPaid($comment = null) {
            if ($this->isPaid() and $user = $this->getOne('User')) {
                $fee = $this->xpdo->newObject('Brevis\Model\Fee', [
                    'type_id' => $this->refundType,
                    'sum' => $this->cost,
                    'user_id' => $user->id,
                    'order_id' => $this->id,
                    'comment' => $comment,
                ]);
                if ($fee->save() !== false) {
                    $this->set('is_paid', 0);
                    $this->save();
                }
            }
        }
        
        /**
        * Отправляет уведомление покупателю
        * @return void
        */
        public function notifyBuyer($controller = null) {
            if (!empty($controller)) {
                $this->controller = $controller;
            }
//            $status = $this->getOne('Status');
            $status = $this->xpdo->getObject('Brevis\Model\OrderStatus', $this->status_id);
            $emailUser = $this->getOne('User');
            $content = 'Вашему заказу #'.$this->id.' ('.$this->item_name.') был присвоен статус «'.$status->name.'».';
            $this->_sendEmail($emailUser, $content);
        }
        
        /**
        * Отправляет уведомление покупателю
        * @return void
        */
        public function notifySupplierCancelled($controller = null) {
            if (!empty($controller)) {
                $this->controller = $controller;
            }
            $emailUser = $this->xpdo->getObjectGraph('Brevis\Model\Sklad', ['Supplier' => ['User']], $this->sklad_id);
            $cc = $emailUser->additional_emails;
            $emailUser = $emailUser->Supplier->User;
            $content = 'Заказ #'.$this->id.' ('.$this->item_name.') был отменен покупателем.';
            $this->_sendEmail($emailUser, $content, $cc);
        }
        
        /**
        * Отправляет уведомление поставщику об оплате заказа
        * @return void
        */
        public function notifySupplierPaid($controller = null) {
            if (!empty($controller)) {
                $this->controller = $controller;
            }
            $emailUser = $this->xpdo->getObjectGraph('Brevis\Model\Sklad', ['Supplier' => ['User']], $this->sklad_id);
            $cc = $emailUser->additional_emails;
            $emailUser = $emailUser->Supplier->User;
            $content = 'Заказ #'.$this->id.' ('.$this->item_name.') оплачен.';
            $this->_sendEmail($emailUser, $content, $cc);
        }
        
        /**
         * Отправляет письмо
         * @param \Brevis\Model\User $emailUser
         * @param string $content
         * @param string $cc Additional emails
         * @return void
        */
        private function _sendEmail($emailUser, $content, $cc = '') {
            if (empty($this->controller)) { return; }
            $processor = $this->controller->core->runProcessor('Mail\Send', [
                'toName' => $emailUser->name,
                'toMail' => $emailUser->email,
                'cc' => $cc,
                'subject' => 'Статус заказа',
                'body' => $this->controller->template('_mail', [
                    'name' => $emailUser->name,
                    'content' => $content,
                    ], $this),
            ]);

            if (!$processor->isSuccess()) {
                $this->controller->core->logger->error('Не удалось отправить письмо об изменении статуса заказа '.$this->id);
            }
        }
        
        /**
         * Проверяет, был ли оплачен заказ
         * @return bool
         */
        public function isPaid() {
            return $this->is_paid == 1;
        }
        
    }
    