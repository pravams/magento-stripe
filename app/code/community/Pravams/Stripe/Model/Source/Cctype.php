<?php
/**
 * Pravams Stripe Module
 *
 * @category    Pravams
 * @package     Pravams_Stripe
 * @copyright   Copyright (c) 2015 Pravams LLC. (http://www.pravams.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Pravams_Stripe_Model_Source_Cctype extends Mage_Payment_Model_Source_Cctype
{
    public function getAllowedTypes(){
        return array('VI', 'MC', 'AE', 'DI');
    }
}
