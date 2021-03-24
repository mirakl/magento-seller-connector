<?php

class MiraklSeller_Core_Block_Adminhtml_Widget_Grid_Column_Renderer_Action_Select
    extends MiraklSeller_Core_Block_Adminhtml_Widget_Grid_Column_Renderer_Action_Links
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

        if (sizeof($actions) == 1 && !$this->getColumn()->getNoLink()) {
            return parent::render($row);
        }

        $out = '<select class="action-select" onchange="varienGridAction.execute(this);">'
            . '<option value=""></option>';
        $i = 0;
        foreach ($actions as $action) {
            $i++;
            if (is_array($action)) {
                $add = true;
                if (isset($action['conds'])) {
                    foreach ($action['conds'] as $key => $value) {
                        if ($row->getData($key) != $value) {
                            $add = false;
                            break;
                        }
                    }

                    unset($action['conds']);
                }

                if ($add) {
                    $out .= $this->_toOptionHtml($action, $row);
                }
            }
        }

        $out .= '</select>';

        return $out;
    }
}
