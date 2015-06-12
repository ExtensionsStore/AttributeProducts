<?php 

/**
 * Widget block
 *
 * @category   Aydus
 * @package    Aydus_AttributeProducts
 * @author     Aydus <davidt@aydus.com>
 */

class Aydus_AttributeProducts_Block_Widget extends Mage_Catalog_Block_Product_List implements Mage_Widget_Block_Interface
{	        
    protected $_template = 'catalog/product/list.phtml';
    
    /**
     * Set widget configuration to list collection and toolbar block
     * @see Mage_Catalog_Block_Product_List::_beforeToHtml()
     */
    protected function _beforeToHtml()
    {        
        if ($this->getData('template')){
            
            $this->_template = $this->getData('template');
        }
        
        if ($this->getData('toolbar')){
            
            $toolbar = $this->getData('toolbar');
            
            try {
                
                $layout = $this->getLayout();
                $toolbarBlock = $this->getLayout()->createBlock($toolbar);
                $toolbarBlock->setIsAnonymous(false)->setNameInLayout('toolbar');
                
                if ($toolbarBlock){
                
                    if ($this->getData('mode')){
                        $mode = $this->getData('mode');
                        $toolbarBlock->setData('_current_grid_mode',$mode);
                    }
                
                    $layout->setBlock('toolbar', $toolbarBlock);
                    $this->setToolbarBlockName('toolbar');
                    $this->setChild('toolbar', $toolbarBlock);
                }
                
            } catch(Exception $e){
                
                Mage::log($e->getMessage(),null,'aydus_attributeproducts.log');
            }
            
        }
                
        return parent::_beforeToHtml();
    }
    
    /**
     * Add attributes and widget configuration to collection
     * Collection is initially raw, no layer or category loaded
     * If sort by letter is used, an unfiltered collection will be added to toolbar
     * 
     */
    protected function _getProductCollection()
    {
        if (is_null($this->_productCollection)) {
            
            $this->_productCollection = Mage::getModel('catalog/product')->getCollection();
            
            $attributesStr = $this->getAttribute();
            $attributes = explode(';', $attributesStr);
            
            if (is_array($attributes) && count($attributes)>0){
                
                $attributeReferencesStr = $this->getAttributeReferences();
                $attributeReferences = explode(';', $attributeReferencesStr);
                $attributesOperatorsStr = $this->getAttributeOperators();
                $attributesOperators = explode(';', $attributesOperatorsStr);
                $attributesValuesStr = $this->getAttributeValues();
                $attributesValues = explode(';', $attributesValuesStr);
                
                foreach ($attributes as $i=>$attributeCode){
                    
                    $attribute = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product',$attributeCode);
                    
                    if ($attribute && $attribute->getId()){
                        
                        $attributeReference = $attributeReferences[$i];
                        $attributesOperator = ($attributesOperators[$i]) ? $attributesOperators[$i] : 'eq';
                        $attributesValue = (strtolower($attributesValues[$i]) === 'true' || strtolower($attributesValues[$i]) === 'false') ? filter_var($attributesValues[$i], FILTER_VALIDATE_BOOLEAN) : $attributesValues[$i];
                        
                        $filter = ($attributesValue) ? array($attributesOperator => $attributesValue) : array('notnull' => true);
                        
                        if ($attributeReference && $attributesValue){
                            
                            $collection = Mage::getResourceModel('catalog/product_collection');
                            $collection->addAttributeToFilter($attributeReference, $filter);
                            $values = $collection->getColumnValues($attributeReference);
                            
                            $this->_productCollection->addAttributeToFilter($attributeCode, array('in' => $values ));
                            
                        } else {
                            
                            $this->_productCollection->addAttributeToFilter($attributeCode, $filter);                        
                        }
                        
                    }
                    
                }
                
                if ((int)$this->getCategoryId()){
                
                    $categoryId = (int)$this->getCategoryId();
                    $category = Mage::getModel('catalog/category')->load($categoryId);
                    if ($category->getId()){
                        
                        $this->_productCollection->addCategoryFilter($category);
                    }
                }                
                
                if ($this->getNumProducts()){
                    $size = $this->getNumProducts();
                    $this->_productCollection->getSelect()->limit($size);
                    $toolbar = $this->getChild('toolbar');
                    $toolbar->setData('limit',$size);
                    $toolbar->setData('_current_limit',$size);
                }
                
                if ($this->getOrderBy()){
                    
                    $orderBy = $this->getOrderBy();
                    $this->setSortBy($orderBy);

                    if ($orderBy == 'letter'){
                        
                        $letter = $this->getRequest()->getParam($this->getLetterParam());
                        
                        if (preg_match('/^[A-Z0-9]$/i', $letter)){
                            
                            //clone the collection AND the select so there's an unfiltered collection for the toolbar 
                            $letterCollection = clone $this->_productCollection;
                            $letterSelect = clone $letterCollection->getSelect();
                            $reflectionClass = new ReflectionClass($letterCollection);
                            $reflectionProperty = $reflectionClass->getProperty('_select');
                            $reflectionProperty->setAccessible(true);
                            $reflectionProperty->setValue($letterCollection, $letterSelect);
                            $letterCollection->addAttributeToSelect('name');
                            $toolbar = $this->getChild('toolbar');
                            $toolbar->setLetterCollection($letterCollection);
                            
                            $this->_productCollection->addFieldToFilter('name', array('like' => "$letter%"));
                        }
                        
                        $this->_productCollection->setOrder('name', 'ASC');        
                                                                
                    } else {
                        
                        $this->_productCollection->setOrder($orderBy, 'ASC');
                    }
                    
                }
                               
            }
                    
        }
        
        return $this->_productCollection;
    }
    
    public function getLetterParam()
    {
        if (!$this->getData('letter_param')){
    
            $this->setData('letter_param', 'l');
        }
    
        return $this->getData('letter_param');
    }
	
}