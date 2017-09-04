<?php
    
    /**
     * Проверка просроченных заказов в базе
     */

    namespace Brevis\Components\Orders;

    use Brevis\Components\Component as Component;
    use Brevis\Controller as Controller;

    class Orders extends Component {
        
        /** @var int Количество "дней жизни" неоплаченных заказов */
        public $daysWait = PROJECT_ORDERS_DAY_WAIT;

        private $_report = ['total' => 0, 'orders' => []]; // отчет
        
        function __construct($core, $config = []) {
            parent::__construct($core, $config);                
            $this->controller = new Controller($this->core);
        }
        
        /**
         * Отправляет уведомление
         * @param Brevis\Model\User $user
         * @param string $subject
         * @param string $content
         * @return void
         */
        private function _sendEmail($user, $subject, $content) {
            $processor = $this->core->runProcessor('Mail\Send', [
                'toName' => $user->name,
                'toMail' => $user->email,
                'subject' => $subject,
                'body' => $this->controller->template('_mail', [
                    'name' => $user->name,
                    'content' => $content,
                    ], $this),
            ]);

            if (!$processor->isSuccess()) {
                $this->core->logger->error('['.get_class().'] Не удалось отправить письмо');
            }
        }
        
        /**
         * Проверка неоплаченных заказов
         * @return void
         * 
         */
        private function _checkNotPaidOrders() {
            $subject = 'Заказ удален';
            $orders = $this->core->xpdo->getCollection('\Brevis\Model\Order', [
                'DATE_ADD(`Order`.`updatedon`, INTERVAL '.$this->daysWait.' DAY) < NOW()',
                'status_id' => 4,
            ]);
            foreach ($orders as $order) {
                $content = 'Заказ #'.$order->id.' ('.$order->item_name.') был удален, как неоплаченный вовремя.';
                $orderId = $order->id;
                if ($order->remove()) {
                    $this->_sendEmail($order->getOne('User'), $subject, $content);
                    $userSupplier = $this->core->xpdo->getObject('\Brevis\Model\User', $order->getOne('Item')->supplier_id);
                    $this->_sendEmail($userSupplier, $subject, $content);
                    $this->_report['total']++;
                    $this->_report['orders'][] = $orderId;
                }
            }
            $this->logger->info('Удалено неоплаченных заказов: ' . $this->_report['total'] . ' (' . implode(', ', $this->_report['orders']) . ')');
        }
        
        /**
         * Запуск процесса
         */
        public function run() {
            $this->_checkNotPaidOrders();
        }
        
    }