<?php

namespace DIQA\ChemExtension\PubChem;

class PubChemCategoriesResult extends PubChemAbstractResult {


    /**
     * PubChemCategoriesResult constructor.
     * @param $result
     */
    public function __construct($result)
    {
        parent::__construct($result);
    }

    public function hasVendors(): bool
    {
        $categories = $this->result->SourceCategories->Categories ?? [];
        if (count($categories) === 0) return false;
        foreach($categories as $category) {
            if ($category->Category == 'Chemical Vendors') {
                return count($category->Sources) > 0;
            }
        }
        return false;
    }


}