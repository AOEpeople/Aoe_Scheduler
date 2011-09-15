<?php
class Aoe_Scheduler_Model_Mysql4_Config extends Mage_Core_Model_Mysql4_Config {

    /**
     * Delete config value using a like statment.
     *
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     * @return Mage_Core_Store_Mysql4_Config
     */
    public function deleteConfigUsingLike($path, $scope, $scopeId) {
        $writeAdapter = $this->_getWriteAdapter();
        $writeAdapter->delete($this->getMainTable(), array(
            $writeAdapter->quoteInto('path LIKE ?', $path),
            $writeAdapter->quoteInto('scope=?', $scope),
            $writeAdapter->quoteInto('scope_id=?', $scopeId)
        ));
        return $this;
    }
	
}