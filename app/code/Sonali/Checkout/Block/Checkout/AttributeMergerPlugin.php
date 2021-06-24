<?php
namespace Sonali\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\AttributeMerger;

/**
 * Class AttributeMerger
 */
class AttributeMergerPlugin
{
    /**
     * @param AttributeMerger $subject
     * @param $result
     * @return mixed
     */
    public function afterMerge(AttributeMerger $subject, $result)
    {
        $result['telephone']['validation'] = [
            'required-entry'  => true,
            //'max_text_length' => 10,
            'phoneUK' => true
        ];
        $result['region']['validation'] = [
            'required-entry'  => true,
            'validate-state' => true
        ];
        $result['postcode']['validation'] = [
            'required-entry'  => true,
            'validate-zip-international' => true
        ];
        return $result;
    }
}
