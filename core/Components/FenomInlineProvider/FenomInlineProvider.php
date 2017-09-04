<?php

    namespace Brevis\Components\FenomInlineProvider;
    use Fenom\ProviderInterface;

    /**
     * Inline template provider
     */
    class FenomInlineProvider implements ProviderInterface {

        /**
         * @param string $tpl
         * @return bool
         */
        public function templateExists($tpl) {
            return !empty($tpl) && strpos($tpl, '@INLINE ') === 0;
        }

        /**
         * @param string $tpl
         * @param int $time
         * @return string
         */
        public function getSource($tpl, &$time) {
            return substr($tpl, 8);
        }

        /**
         * @param string $tpl
         * @return int
         */
        public function getLastModified($tpl) {
            return time();
        }

        /**
         * Verify templates (check mtime)
         *
         * @param array $templates [template_name => modified, ...] By conversation, you may trust the template's name
         * @return bool if true - all templates are valid else some templates are invalid
         */
        public function verify(array $templates) {
            return true;
        }

        /**
         * Get all names of template from provider
         * @return array|\Iterator
         */
        public function getList() {
            return null;
        }
    }
    