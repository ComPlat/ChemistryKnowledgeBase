<?php


namespace DIQA\ChemExtension\FormInputs;
use Html;

class RedoxFormInput extends \PFFormInput {

    public static function getName()
    {
        return "redoxinput";
    }

    public static function getHTML( $cur_value, $input_name, $is_mandatory, $is_disabled, array $other_args )
    {
        global $wgPageFormsTabIndex, $wgPageFormsFieldNum, $wgPageFormsEDSettings;

        $input_id = 'input_' . $wgPageFormsFieldNum;

        $inputAttrs = [
            'id' => $input_id,
            'name' => $input_name,
            'class' => 'ce_redoxinput',
            'tabindex' => $wgPageFormsTabIndex,
            'value' => $cur_value,
            'disabled' => $is_disabled
        ];

        $inputText = Html::rawElement( 'input', $inputAttrs);
        $spanID = 'span_' . $wgPageFormsFieldNum;
        $spanClass = 'ce_redoxinput_span';
        $spanAttrs = [
            'id' => $spanID,
            'class' => $spanClass,
            'data-input-type' => 'combobox'
        ];

        $buttonText = Html::rawElement('button', [ 'class' => 'ce_redoxinput_button'], 'Add/remove');
        return Html::rawElement( 'span', $spanAttrs, $inputText . $buttonText );
    }

    public function getHtmlText(): string {
        return self::getHTML(
            $this->mCurrentValue,
            $this->mInputName,
            $this->mIsMandatory,
            $this->mIsDisabled,
            $this->mOtherArgs
        );
    }

    public function getResourceModuleNames() {
        return [ 'ext.diqa.chemextension.redoxinput' ];
    }
}