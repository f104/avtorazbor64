<?php

namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Help extends Controller {

    public $name = 'Помощь';
    public $path;
    public $content = 'Help! I need somebody, Help!';
    
    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->path = PROJECT_ASSETS_PATH . 'help/';
    }
    
    /**
    * @return string
    */
    public function run() {
        if (!empty($_REQUEST['article'])) {
            $article = $this->_getArticle($_REQUEST['article']);
        } else {
            $article = $this->_getArticle('main');
        }
        if ($article) {
            $this->content = $this->_parseMd($article);
        }
        $data = [
            'content' => $this->content,
        ];
        $html = $this->template($this->template, $data, $this);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', ['html' => $html]);
        }
        return $html;
    }
    
    private function _getArticle($article) {
        $filename = $this->path . $article . '.md';
        if (file_exists($filename)) {
            return file_get_contents($filename);
        }
        return false;
    }
    
    private function _parseMd($string) {
        $parsedown = new ExtendedParsedown();
        $parsedown->uri = $this->uri;
        if (isset($_REQUEST['resource'])) {
            $parsedown->active = $_REQUEST['resource'];
        }
        $s = $parsedown->text($string);
//        var_dump($parsedown->toc);
        return $s;
    }
}

class ExtendedParsedown extends \Parsedown {
    
    public $toc = '';
    public $curLevel = 0;
    public $uri = null;
    public $active = null;


    public function text($text) {
        $text = parent::text($text);
        $this->closeToc();
        $text = $this->toc . $text;
        return $text;
    }
    
    protected function blockHeader($Line)
    {
        if (isset($Line['text'][1]))
        {
            $level = 1;

            while (isset($Line['text'][$level]) and $Line['text'][$level] === '#')
            {
                $level ++;
            }

            if ($level > 6)
            {
                return;
            }

            $text = trim($Line['text'], '# ');

            $Block = array(
                'element' => array(
                    'name' => 'h' . min(6, $level),
                    'text' => $text,
                    'handler' => 'line',
                ),
            );
            $this->addToc($Block, $level);
            
            return $Block;
        }
    }
    
    protected function addToc(&$block, $level) {
        if ($level > $this->curLevel) {
            $this->toc .= '<ul class="js-md-toc">';
        } else if ($level < $this->curLevel) {
            $this->toc .= str_repeat("</li></ul>\n", $this->curLevel - $level) . "</li>\n";
        } else {
            $this->toc .= "</li>\n";
        }
        $this->toc .= '<li>'.preg_replace('/(.*)<a name="(.*)"><\/a>$/', '<a href="'.$this->uri.'#\2">\1</a>', $block['element']['text'])."\n";
        if (!empty($this->active)) {
            $block['element']['text'] = str_replace('<a name="'.$this->active.'">', '<a name="'.$this->active.'" class="js-md-active">', $block['element']['text']);
        }
        
        
//        $block['element']['text'] = '<a name="'.$block['element']['text'].'"></a>'.$block['element']['text'];
//        $this->toc .= '<li><a href="'.$this->uri.'#'.$block['element']['text'].'">'.$block['element']['text']."</a>\n";
//        $block['element']['text'] = '<a name="'.$block['element']['text'].'"></a>'.$block['element']['text'];
        $this->curLevel = $level;
    }
    
    protected function closeToc() {
        $this->toc .= str_repeat("</li></ul>\n", $this->curLevel-1) . "\n";
    }
    
}