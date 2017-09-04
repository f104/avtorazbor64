<?php

    namespace Brevis\Components\Form;

    class Input {

        public $options = ['id' => null, 'name' => null, 'type' => 'text', 'class' => 'form-control'];
        public $template = '
            <div class="{$wrapperClass}">
            {$label}
            <input {$options}>
            <div class="text-danger">{$error}</div>
            <p class="help-block">{$help}</p>
            {$html}
            </div>';
        public $helpTemplate = ' <a href="{$url}" class="mp-ajax-popup-align-top link-clear"><i class="fa fa-question-circle"></i></a>';

        protected $_label = null;
        protected $_value = null;
        protected $_error = null;
        protected $_help = null;
        protected $_html = null;
        protected $_wrapperClass = 'form-group';
        protected $_wrapperErrorClass = 'has-error';
        protected $_wrapperRequiredClass = 'required';
        protected $_form;

        public function __construct($name, array $params, Form $form) {
            if (!empty($params['template'])) {
                $this->template = $params['template'];
                unset($params['template']);
            }
            $this->options = array_merge($this->options, $params);
            $this->_form = $form;
            if (empty($this->options['id'])) {
                $this->options['id'] = $form->options['id'] . '_' . $name;
            }
            $this->options['name'] = $name;
        }
        
        public function validate($rules) {
            if (is_string($rules)) {
                $rules = explode(',', $rules);
                array_walk($rules, 'trim');
            }
            $name = $this->options['name'];
            foreach ($rules as $rule) {
                $this->_form->addValidate($name, $rule);
            }
            return $this;
        }

        public function draw($data = []) {
            $wrapperClass = $this->_wrapperClass;
            if (!empty($this->_error)) {
                $wrapperClass .= ' '.$this->_wrapperErrorClass;
            }
            if (in_array('required', $this->options)) {
                $wrapperClass .= ' '.$this->_wrapperRequiredClass;
            }
            $data = array_merge($data, [
                '{$label}' => $this->_label,
                '{$options}' => $this->_form->buildOptions($this->options),
                '{$error}' => $this->_error,
                '{$help}' => $this->_help,
                '{$html}' => $this->_html,
                '{$wrapperClass}' => $wrapperClass,
            ]);
            return $this->_form->parseTemplate($data, $this->template);
        }

        public function addLabel($label, $helpUrl = null) {
            $this->_label = '<label for="'.$this->options['id'].'">'.$label.'</label>';
            if (!empty($helpUrl)) {
                $this->_label .= $this->_form->parseTemplate(['{$url}' => $helpUrl], $this->helpTemplate);
            }
            return $this;
        }
        
        public function addOption($k, $v = null) {
            if (!empty($v)) {
                $this->options[$k] = $v;
            } else {
                $this->options[] = $k;
            }
            return $this;
        }
        
        public function addError($error) {
            $this->_error = $error;
            return $this;
        }
        
        public function getError() {
            return $this->_error;
        }
        
        public function hasError() {
            return !empty($this->_error);
        }
        
        public function addHelp($help) {
            $this->_help = $help;
            return $this;
        }
        
        public function addHtml($html) {
            $this->_html = $html;
            return $this;
        }
        
        public function setWrapperClass($class) {
            $this->_wrapperClass = $class;
            return $this;
        }
        
        public function addWrapperClass($class) {
            $classes = explode(' ', $this->_wrapperClass);
            $classes[] = $class;
            $this->_wrapperClass = implode(' ', $classes);
            return $this;
        }

        public function setValue($value) {
            $this->_value = $value;
            $this->options['value'] = htmlspecialchars($value);
            return $this;
        }

        public function getValue() {
            return $this->_value;
        }

    }
    
    class Password extends Input  {

        public $options = ['id' => null, 'name' => null, 'type' => 'password', 'class' => 'form-control'];
        public $template = '
            <div class="{$wrapperClass}">
            {$label}
            <div class="password-view-wrapper eye-closed">
                <i class="fa fa-eye-slash" title="Показать пароль"></i>
                <input {$options}>
            </div>
            <div class="text-danger">{$error}</div>
            <p class="help-block">{$help}</p>
            </div>';

    }
    
    class Hidden extends Input  {

        public $options = ['id' => null, 'name' => null, 'type' => 'hidden'];
        public $template = '<input {$options}>';

    }
    
    class Checkbox extends Input {
        
        public $options = ['id' => null, 'name' => null, 'type' => 'checkbox'];
        public $template = '
            <div class="{$wrapperClass}">
            <label><input {$options} value="1">{$label}</label>
            <div class="text-danger">{$error}</div>
            <p class="help-block">{$help}</p>
            </div>';

        protected $_wrapperClass = 'checkbox';
        
        public function addLabel($label, $helpUrl = null) {
            $this->_label = $label;
            return $this;
        }

        public function setValue($value) {
            $this->_value = $value;
            if (!empty($this->_value)) {
                $this->options[] = 'checked';
            } else {
                $key = array_search('checked', $this->options);
                if ($key !== false) {
                    unset($this->options[$key]);
                }
            }
            return $this;
        }
        
    }
    
    class Radio extends Input {
        
        public $options = ['id' => null, 'name' => null, 'type' => 'radio'];
        public $template = '
            <div class="{$wrapperClass}">
                <div class="radio">
                    {$radios}
                </div>
                <div class="text-danger">{$error}</div>
                <p class="help-block">{$help}</p>
            </div>';
        public $templateRadio = '<label class="{$labelClass}"><input {$options}> {$label}</label>';
        public $labelClass = 'radio-inline';

        private $_radios;
        
        public function addOne($value, $label = '', $options = []) {
            $options = array_merge($this->options, $options);
            $options['value'] = htmlspecialchars($value);
            if ($value == $this->_value) { $options[] = 'checked'; }
            $labelClass = $this->labelClass;
            if (in_array('disabled', $options)) { $labelClass .= ' disabled'; }
            $this->_radios[$value] = [
                'label' => $label,
                'labelClass' => $labelClass,
                'options' => $options,
            ];
            return $this;
        }


        public function draw($data = []) {
            $output = [];
            foreach ($this->_radios as $k => $radio) {
                $data = [
                    '{$label}' => $radio['label'],
                    '{$labelClass}' => $radio['labelClass'],
                    '{$options}' => $this->_form->buildOptions($radio['options']),
                ];
                $output[$k] = $this->_form->parseTemplate($data, $this->templateRadio);
            }
            $data['{$radios}'] = implode('', $output);
            return parent::draw($data);
        }

        public function addLabel($label, $helpUrl = null) {
            return $this;
        }
        
        public function setValue($value) {
            $this->_value = $value;
            if (isset($this->_radios[$value])) {
                $this->_radios[$value]['options'][] = 'checked';
            }
            return $this;
        }
        
    }
    
    class Select extends Input {
        
        public $options = ['id' => null, 'name' => null, 'class' => 'form-control'];
        public $template = '
            <div class="{$wrapperClass}">
            {$label}
            <select {$options}>{$selectOptions}</select>
            <div class="text-danger">{$error}</div>
            <p class="help-block">{$help}</p>
            {$html}
            </div>';
        
        private $_selectOptions = [];
        protected $_withEmptyOption = false;
        protected $_emptyOptionText = false;
        
        public function draw($data = []) {
            $data = ['{$selectOptions}' => $this->_drawSelectOptions()];
            return parent::draw($data);
        }
        
        public function setSelectOptions($options) {
            $this->_selectOptions = $options;
            return $this;
        }
        
        public function withEmptyOption($text = '') {
            $this->_withEmptyOption = true;
            $this->_emptyOptionText = $text;
            return $this;
        }
        
        private function _drawSelectOptions() {
            $opt = [];
            if ($this->_withEmptyOption) {
                $opt[] = '<option value="">'.$this->_emptyOptionText.'</option>';
            }
            if (!empty($this->_selectOptions)) {
                foreach ($this->_selectOptions as $k => $v) {
                    $o = '<option value="'.htmlspecialchars($k).'"'; 
                    // '' == 0, всегда использовать === тоже не получается
                    if ($this->_value === '') { 
                        if ($k === $this->_value) { $o .= ' selected'; }
                    } else {
                        if ($k == $this->_value) { $o .= ' selected'; }
                    }
                    $o .= '>'.$v.'</option>';
                    $opt[] = $o;
                }
            }
            return implode('', $opt);
        }
        
        public function setValue($value) {
            $this->_value = $value;
            return $this;
        }
        
    }
    
    class Textarea extends Input {

        public $options = ['id' => null, 'name' => null, 'class' => 'form-control'];
        public $template = '
            <div class="{$wrapperClass}">
            {$label}
            <textarea {$options}>{$value}</textarea>
            <div class="text-danger">{$error}</div>
            <p class="help-block">{$help}</p>
            </div>';

        protected $_label = null;
        protected $_value = null;
        protected $_error = null;
        protected $_help = null;
        protected $_wrapperClass = 'form-group';
        protected $_wrapperErrorClass = 'has-error';
        protected $_wrapperRequiredClass = 'required';
        protected $_form;

        public function draw($data = []) {
            $data = ['{$value}' => $this->_value];
            return parent::draw($data);
        }

        public function setValue($value) {
            $this->_value = $value;
            return $this;
        }

    }
    
    class Button {

        public $options = ['id' => null, 'type' => null, 'class' => 'btn btn-primary'];
        
        public $template = '<button {$options}>{$text}</button>';

        protected $_text;
        protected $_form;

        public function __construct($text, array $params, Form $form) {
            $this->options = array_merge($this->options, $params);
            $this->_text = $text;
            $this->_form = $form;
        }

        public function draw() {
            $data = [
                '{$options}' => $this->_form->buildOptions($this->options),
                '{$text}' => $this->_text,
            ];
            return $this->_form->parseTemplate($data, $this->template);
        }

    }
    
    class Link {

        public $options = ['id' => null, 'href' => null, 'class' => 'btn btn-default', 'title' => null];
        
        public $template = '<a {$options}>{$text}</a>';

        protected $_text;
        protected $_form;

        public function __construct($text, array $params, Form $form) {
            $this->options = array_merge($this->options, $params);
            $this->_text = $text;
            $this->_form = $form;
        }

        public function draw() {
            $data = [
                '{$options}' => $this->_form->buildOptions($this->options),
                '{$text}' => $this->_text,
            ];
            return $this->_form->parseTemplate($data, $this->template);
        }

    }

    class Form {

        /** @var string form template */
        public $template = '<form {$options}> {$legend} {$fields} {$buttons} </form>';

        /** @var string form options */
        public $options = [
            'method' => 'POST',
            'action' => null,
            'id' => 'form',
        ];
        
        public $legend;

        /** @var array form fields */
        public $fields = [];
        private $_oldValues= [];

        /** @var array form buttons */
        public $buttons = [];

        /** @var array $field => $validator1, $validator2...  * */
        public $validate = [];
        
        // правила валидации
        private $_validators = array(
            'required',
            'email',
            'nochange',
            'maxlength',
            'pattern',
        );

        /** @var array сообщения об ошибках валидации */
        public $errorsMsg = array(
            'required' => 'Это обязательное поле.',
            'email' => 'Введите корректный e-mail.',
            'nochange' => 'Это поле не может быть изменено.',
            'maxlength' => 'Количество символов превышает максимально разрешенное для этого поля.',
            'pattern' => 'Строка не соответствует указанному шаблону.',
        );
//        private $errors = array(); // массив с ошибками

        public function __construct($params, &$controller) {
            $this->options = array_merge($this->options, $params);
            $this->controller = $controller;
        }
        
        // TODO __call
//        public function __call($name, $arguments) {
//            var_dump($arguments);
//            $className =  __NAMESPACE__ . '\\' . ucfirst($className);
//            if (class_exists($className)) {
//                return;
//            }
//        }
        public function addValidate($field, $rule) {
            $this->validate[$field][] = $rule;
        }

        protected function _field($className, $name, $params) {
            $className =  __NAMESPACE__ . '\\' . ucfirst($className);
            $input = new $className($name, $params, $this);
            // добавляем очевидные валидаторы
            if (in_array('required', $input->options)) {
                $this->addValidate($name, 'required');
            }
            if (isset($input->options['maxlength'])) {
                $this->addValidate($name, 'maxlength');
            }
            if (isset($input->options['pattern'])) {
                $this->addValidate($name, 'pattern');
            }
            if (isset($input->options['type']) and $input->options['type'] == 'email') {
                $this->addValidate($name, 'email');
            }
            $this->fields[$name] = $input;
            return $this->fields[$name];
        }

        public function input($name, $params = []) {
            return $this->_field('Input', $name, $params);
        }
        
        public function password($name, $params = []) {
            return $this->_field('Password', $name, $params);
        }
        
        public function hidden($name, $params = []) {
            return $this->_field('Hidden', $name, $params);
        }
        
        public function select($name, $params = []) {
            return $this->_field('Select', $name, $params);
        }
        
        public function checkbox($name, $params = []) {
            return $this->_field('Checkbox', $name, $params);
        }
        public function radio($name, $params = []) {
            return $this->_field('Radio', $name, $params);
        }
        
        public function textarea($name, $params = []) {
            return $this->_field('Textarea', $name, $params);
        }
        
        public function button($text, $params) {
            $button = new Button($text, $params, $this);
            $this->buttons[$text] = $button;
            return $this->buttons[$text];
        }
        
        public function link($text, $params) {
            $button = new Link($text, $params, $this);
            $this->buttons[$text] = $button;
            return $this->buttons[$text];
        }
        
        public function legend($string) {
            $this->legend = $string;
        }

        public function draw() {
            $fields = [];
            $buttons = [];
            foreach ($this->fields as $field) {
                $fields[] = $field->draw();
            }
            foreach ($this->buttons as $button) {
                $buttons[] = $button->draw();
            }
            $data = [
                '{$options}' => $this->buildOptions($this->options),
                '{$legend}' => !empty($this->legend) ? '<legend>'.$this->legend.'</legend>' : '',
                '{$fields}' => implode(' ', $fields),
                '{$buttons}' => implode(' ', $buttons),
//            'errors' => $this->errors,
            ];
            return $this->parseTemplate($data, $this->template);
        }

        public function parseTemplate($data, $tpl) {
            return str_replace(array_keys($data), $data, $tpl);
        }

        public function buildOptions(array $options) {
            $opt = [];
            foreach ($options as $k => $v) {
                if (!empty($v)) {
                    $opt[] = is_int($k) ? $v : $k . '=' . '"' . $v . '"';
                }
            }
            return implode(' ', $opt);
        }
        
        /**
         * Чистит пользовательский ввод
         * @param string $value
         */
        private function clearInput(&$value) {
            $value = trim(strip_tags($value));
        }
        
        /**
         * Возвращает истину, если форма отработала и прошла валидацию
         * @return boolean
         */
        public function process() {
            if ($_SERVER['REQUEST_METHOD'] === strtoupper($this->options['method'])) {
                $this->_oldValues = $this->getValues();
                $this->_setValues();
                return $this->validate();
            }
            return false;
        }
        
        /**
         * Проверяет, были ли изменения в форме
         * @return bool
         */
        public function hasChanged() {
            return !($this->_oldValues == $this->getValues());
        }
        
        public function unsetField($name) {
            unset($this->_oldValues[$name], $this->fields[$name]);
        }
        
        public function getValues() {
            $values = [];
            foreach ($this->fields as $key => $field) {
                $values[$key] = $field->getValue();
            }
            return $values;
        }

        /**
         * Проверка пользовательского ввода
         * @return boolean
         */
        public function validate() {
            $validated = true;
            foreach ($this->fields as $key => $field) {
                if (array_key_exists($key, $this->validate)) {
                    foreach ($this->validate[$key] as $rule) {
                        if (in_array($rule, $this->_validators) and ! $this->_validateInput($field->getValue(), $rule, $key)) {
                            $field->addError($this->errorsMsg[$rule]);
                            $validated = false;
                            break;
                        }
                    }
                }
            }
            return $validated;
        }

        private function _setValues() {
            array_walk($_REQUEST, array($this, 'clearInput'));
            foreach ($this->fields as $key => $field) {
                $v = isset($_REQUEST[$key]) ? $_REQUEST[$key] : ''; 
                $this->fields[$key]->setValue($v);
            }
        }

        /**
         * Проверка одного поля
         * @param string $value Пользовательский ввод
         * @param string $rule Правило
         * @param string $key Имя поля (может потребоваться для определенныъ проверок)
         * @return boolean
         */
        private function _validateInput($value, $rule, $key = null) {
            $validated = false;
            switch ($rule) {
                case 'required':
                    if (strlen($value) != 0) {
                        $validated = true;
                    }
                    break;
                case 'email':
                    if ($pos = strpos($value, '@') and $pos != strlen($value) - 1) {
                        $validated = true;
                    }
                    break;
                case 'nochange':
                    if ($value == $this->_oldValues[$key]) {
                        $validated = true;
                    }
                    break;
                case 'maxlength':
                    if (mb_strlen($value) <= $this->fields[$key]->options['maxlength']) {
                        $validated = true;
                    }
                    break;
                case 'pattern':
                    if (empty($value) or preg_match('/'.$this->fields[$key]->options['pattern'].'/', $value)) {
                        $validated = true;
                    }
                    break;
            }
            return $validated;
        }
        
        public function addError($field, $error) {
            $this->fields[$field]->addError($error);
        }
        
        public function hasError() {
            foreach ($this->fields as $key => $field) {
                if ($field->hasError()) {
                    return true;
                }
            }
            return false;
        }
        
        public function getErrors() {
            $errors = [];
            foreach ($this->fields as $key => $field) {
                if ($field->hasError()) {
                    $errors[$key] = $field->getError();
                }
            }
            return $errors;
        }

    }