<?php

namespace Brevis\Components\Form;

class FormFilters extends Form {

    /** @var string form options */
    public $options = [
        'method' => 'GET',
        'action' => null,
        'id' => 'form',
//        'class' => 'form-filters',
    ];
    
    public function setValues($values) {
        unset($values['page']);
        foreach ($this->fields as $name => &$field) {
            if (isset($values[$name])) {
                $field->setValue($values[$name]);
                $field->addWrapperClass('filter-applied');
            } else {
                $field->setValue('');
            }
        }
    }

    public function select($name, $params = []) {
        return $this->_field('SelectFilters', $name, $params);
    }
    
    public function input($name, $params = []) {
        return $this->_field('InputFilters', $name, $params);
    }
    
    public function draw() {
        $this->setValues($this->controller->filters);        
        $this->button('&crarr;', ['type' => 'submit', 'class' => 'btn btn-default']);
        $this->link('Показать все', ['href' => $this->controller->uri]);
        return parent::draw();
    }

}

class SelectFilters extends Select {

    public $options = ['id' => null, 'name' => null, 'class' => 'form-control js-submit'];
    public $template = '
            <div class="{$wrapperClass}">
            {$label}
            <select {$options}>{$selectOptions}</select>
            </div>';
    
    public function __construct($name, array $params, Form $form) {
        parent::__construct($name, $params, $form);
        $this->_withEmptyOption = true;
        $this->_emptyOptionText = $form->controller->lang['all'];
    }
    
}

class InputFilters extends Input {

    public $template = '
        <div class="{$wrapperClass}">
        {$label}
        <input {$options}>
        </div>';
    
}
