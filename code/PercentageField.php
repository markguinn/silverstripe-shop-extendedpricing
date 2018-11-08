<?php
/**
 * Simple form element that makes saving to Percentage
 * DBField easier. Accepts the following kinds of input:
 *  -> 50    = 50%
 *  -> 0.5   = 50%
 *  -> 0.5%  = 0.5%
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 08.20.2013
 * @package shop_extendedpricing
 */
class PercentageField extends NumericField
{
    public function Type()
    {
        return 'percentage numeric text';
    }


    /**
     * @param mixed $val
     * @param array $data
     * @return $this
     */
    public function setValue($val, $data = array())
    {
        // convert to a number
        parent::setValue(str_replace('%', '', $val));
        $dataVal = $this->dataValue();

        // divide by 100 if needed and set again
        if (strpos($val, '%') !== false || $dataVal > 1 || $dataVal < -1) {
            $dataVal = (double)$dataVal / 100.0;
            parent::setValue($dataVal);
        }
        return $this;
    }


    /**
     * @return string
     * @throws Zend_Locale_Exception
     */
    public function Value()
    {
        require_once "Zend/Locale/Format.php";
        $locale = new Zend_Locale($this->getLocale());

        // convert from the stored format to a real number so we can multiply
        $number = Zend_Locale_Format::getNumber(
            (float)$this->clean($this->value),
            array('locale' => $locale)
        );
        $number *= 100.0;

        // convert back to string
        $val = Zend_Locale_Format::toNumber($number, array('locale' => $locale));
        return $val . '%';
    }
}
