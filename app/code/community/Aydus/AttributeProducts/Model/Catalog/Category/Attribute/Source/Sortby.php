<?php 

/**
 * Category attribute sort by source options
 *
 * @category   Aydus
 * @package    Aydus_AttributeProducts
 * @author     Aydus <davidt@aydus.com>
 */

class Aydus_AttributeProducts_Model_Catalog_Category_Attribute_Source_Sortby extends Mage_Catalog_Model_Category_Attribute_Source_Sortby
{	
    
    public function toOptionArray()
    {
        $options = $this->getAllOptions();
        $options[] = array('value'=>'letter', 'label'=>'First Letter Alphabetical');
        
        return $options;
    }
	
}