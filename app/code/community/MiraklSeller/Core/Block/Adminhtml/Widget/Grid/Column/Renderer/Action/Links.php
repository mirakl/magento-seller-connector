<?php

class MiraklSeller_Core_Block_Adminhtml_Widget_Grid_Column_Renderer_Action_Links
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    /**
     * @param   Varien_Object   $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $actions = $this->getColumn()->getActions();
        if (empty($actions) || !is_array($actions)) {
            return '&nbsp;';
        }

        $out = array();
        foreach ($actions as $action) {
            if (is_array($action)) {
                $add = true;
                if (isset($action['conds'])) {
                    if (is_array($action['conds'])) {
                        foreach ($action['conds'] as $key => $value) {
                            if ($row->getData($key) != $value) {
                                $add = false;
                                break;
                            }
                        }
                    } elseif ($action['conds'] instanceof \Closure) {
                        $add = $action['conds']($row);
                    }

                    unset($action['conds']);
                }

                if ($add) {
                    $out[] = $this->_toLinkHtml($action, $row);
                }
            }
        }

        return implode('&nbsp;/&nbsp;', $out);
    }
}
