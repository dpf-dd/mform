<?php
/**
 * Author: Joachim Doerr
 * Date: 16.07.16
 * Time: 22:50
 */

class MFormParser extends AbstractMFormParser
{
    /**
     * create the fieldset open element
     * fieldset open
     * @param MFormItem $item
     * @return $this
     * @author Joachim Doerr
     */
    private function generateFieldset(MFormItem $item)
    {
        // if it the first fieldset ? no close the parent
        if ($this->fieldset === true) {
            $item->setCloseFieldset(true); // use item to transport the flag
        } else {
            // set flag for close is fieldset not closed by element input
            $this->fieldset = true;
        }

        // set default class for r5 mform default theme
        MFormItemManipulator::setDefaultClass($item);

        // is close fieldset tag set
        if ($item->isCloseFieldset()) {
            $this->closeFieldset(true);
        }

        // create legend
        $legendElement = new MFormElement();
        $legendElement->setValue($item->getValue());

        // create fieldset open element
        $fieldsetElement = new MFormElement();
        $fieldsetElement->setClass($item->getClass()) // set fieldset default and custom class
            ->setAttributes($this->parseAttributes($item->getAttributes())) // add attributes to fieldset element
            ->setLegend($this->parseElement($legendElement, 'legend', true)); // add parsed legend to fieldset element

        // add fieldset open element to elements list
        $this->elements[] = $this->parseElement($fieldsetElement, 'fieldset-open', true);
        return $this;
    }

    /**
     * create the fieldset close element
     * fieldset close
     * @param bool $fieldset
     * @return $this
     * @author Joachim Doerr
     */
    private function closeFieldset($fieldset = false)
    {
        // is parameter flag true
        if ($fieldset) {
            // set it in fieldset property
            $this->fieldset = $fieldset;
        }
        // if fieldset property true
        if ($this->fieldset === true) {
            $this->fieldset = false; // set it on false for the next
            // add fieldset close element to elements list
            $this->elements[] = $this->parseElement(new MFormElement(), 'fieldset-close', true); // use parse element to load template file
        }
        return $this;
    }

    /**
     * create any no input inline element
     * html, headline, description
     * @param MFormItem $item
     * @return $this
     * @author Joachim Doerr
     */
    private function generateLineElement(MFormItem $item)
    {
        // create templateElement object
        $element = new MFormElement();
        $element->setOutput($item->getValue()); // set output to replace in template
        // add to output element array
        $this->elements[] = $this->parseElement($element, $item->getType());
        return $this;
    }

    /**
     * create input text element
     * hidden, text, password
     * @param MFormItem $item
     * @return $this
     * @author Joachim Doerr
     */
    private function generateInputElement(MFormItem $item)
    {
        // define default template type
        $templateType = 'default';

        // set typ specific vars
        switch ($item->getType()) {
            case 'hidden': // is type hidden set template hidden
                $templateType = 'hidden';
                $item->setLabel(''); // and unset label
                break;
            case 'text-readonly': // is readonly
                MFormAttributeHandler::setAttribute($item, 'readonly', 'readonly'); // add attribute readonly
                break;
        }

        // is full flag true and template type default
        if ($item->isFull() && $templateType == 'default') {
            $templateType = $templateType . '_full'; // add _full to template type
        }

        // default manipulations
        MFormItemManipulator::setVarAndIds($item); // transform ids for template usage
        MFormItemManipulator::setCustomId($item); // set optional custom id
        MFormItemManipulator::setDefaultClass($item); // set default class for r5 mform default theme

        // create element
        $element = new MFormElement();
        // add all replacement elements for template parsing
        $element->setId($item->getId())
            ->setVarId($item->getVarId())
            ->setValue($item->getValue())
            ->setType($item->getType())
            ->setClass($item->getClass())
            ->setAttributes($this->parseAttributes($item->getAttributes())); // parse attributes for use in templates

        // create label element
        $label = new MFormElement();
        $label->setId($item->getId())
            ->setValue($item->getLabel());

        // create templateElement object
        $templateElement = new MFormElement();
        $templateElement->setLabel($this->parseElement($label, 'label', true))
            ->setElement($this->parseElement($element, 'text', true));

        // add to output element array
        $this->elements[] = $this->parseElement($templateElement, $templateType);
        return $this;
    }

    /**
     * create textarea element
     * textarea
     * @param $item
     * @return $this
     * @author Joachim Doerr
     */
    private function generateAreaElement(MFormItem $item)
    {
        // define default template type
        $templateType = 'default';

        // set typ specific vars
        switch ($item->getType()) {
            case 'textarea-readonly':
                $item->setType('textarea'); // type is textarea
                MFormAttributeHandler::setAttribute($item, 'readonly', 'readonly'); // add attribute readonly
                break;
        }

        // is full flag true and template type default
        if ($item->isFull()) {
            $templateType = $templateType . '_full'; // add _full to template type
        }

        // default manipulations
        MFormItemManipulator::setVarAndIds($item); // transform ids for template usage
        MFormItemManipulator::setCustomId($item); // set optional custom id
        MFormItemManipulator::setDefaultClass($item); // set default class for r5 mform default theme

        // create element
        $element = new MFormElement();
        // add all replacement elements for template parsing
        $element->setId($item->getId())
            ->setVarId($item->getVarId())
            ->setValue($item->getValue())
            ->setType($item->getType())
            ->setClass($item->getClass())
            ->setAttributes($this->parseAttributes($item->getAttributes()));

        // create label element
        $label = new MFormElement();
        $label->setId($item->getId())
            ->setValue($item->getLabel());

        // create templateElement object
        $templateElement = new MFormElement();
        $templateElement->setLabel($this->parseElement($label, 'label', true))
            ->setElement($this->parseElement($element, 'textarea', true));

        // add to output element array
        $this->elements[] = $this->parseElement($templateElement, $templateType);
        return $this;
    }

    /**
     * create select or multiselect element
     * select, multiselect
     * @param MFormItem $item
     * @return $this
     * @author Joachim Doerr
     */
    private function generateOptionsElement(MFormItem $item)
    {
        // default manipulations
        MFormItemManipulator::setVarAndIds($item); // transform ids for template usage
        MFormItemManipulator::setCustomId($item); // set optional custom id
        MFormItemManipulator::setDefaultClass($item); // set default class for r5 mform default theme

        // init option element string
        $optionElements = '';
        $itemAttributes = $this->parseAttributes($item->getAttributes()); // parse attributes for output

        // options must te be given
        if (sizeof($item->getOptions()) > 0) {
            // size count
            $count = 0;
            foreach ($item->getOptions() as $key => $value) {
                // is value label we have a opt group
                if (is_array($value)) {
                    // optGroup set
                    $optGroupLabel = $key;
                    $optElements = '';
                    $count++; // + for group label

                    // create options
                    foreach ($value as $vKey => $vValue) {
                        $count++;
                        $optElements .= $this->createOptionElement($item, $vKey, $vValue);
                    }

                    // create opt group element
                    $groupElement = new MFormElement();
                    $groupElement->setOptions($optElements)
                        ->setLabel($optGroupLabel);

                    $optionElements .= $this->parseElement($groupElement, 'optgroup', true);
                } else {
                    $count++;
                    $optionElements .= $this->createOptionElement($item, $key, $value);
                }
            }
            // is size full
            if ($item->getSize() == 'full') {
                // use count to replace #sizefull# placeholder
                $itemAttributes = str_replace('#sizefull#', $count, $itemAttributes);
            }
        }

        // create element
        $element = new MFormElement();
        $element->setId($item->getId())
            ->setVarId($item->getVarId())
            ->setType($item->getType())
            ->setValue($item->getValue())
            ->setAttributes($itemAttributes)
            ->setClass($item->getClass())
            ->setOptions($optionElements);

        if ($item->isMultiple()) {
            // javascript
            $hiddenJavascript = <<<EOT
                <script type="text/javascript">
                  /* <![CDATA[ */
                    $(function () {
                      $("#{$item->getId()}").change(function() {
                        $("#hidden_{$item->getId()}").val($(this).val());
                      });
                    });
                  /* ]]> */
                </script>
EOT;

            $element->setJavascript($hiddenJavascript);

            // hidden element
            $hiddenElement = new MFormElement();
            $hiddenElement->setId('hidden_' . $item->getId())
                ->setVarId($item->getVarId())
                ->setValue($item->getValue())
                ->setType('hidden');

            // hidden input element
            $element->setHidden($this->parseElement($hiddenElement, 'text', true));
        }

        // create label element
        $label = new MFormElement();
        $label->setId($item->getId())
            ->setValue($item->getLabel());

        // create templateElement object
        $templateElement = new MFormElement();
        $templateElement->setLabel($this->parseElement($label, 'label', true))
            ->setElement($this->parseElement($element, 'select', true));

        // add to output element array
        $this->elements[] = $this->parseElement($templateElement, 'default');
        return $this;
    }

    /**
     * helper method to create option elements
     * @param MFormItem $item
     * @param $key
     * @param $value
     * @return mixed
     * @author Joachim Doerr
     */
    private function createOptionElement(MFormItem $item, $key, $value)
    {
        // create element
        $element = new MFormElement();
        $element->setValue($key) // set option key
            ->setLabel($value); // set option label

        $itemValue = $item->getValue();

        // is mode edit and item multiple
        if ($item->getMode() == 'edit' && $item->isMultiple()) {
            // explode the hidden value string
            foreach (explode(',', $itemValue) as $iValue) {
                if ($key == $iValue) { // check is the option key in the hidden string
                    $itemValue = $iValue; // set new item value
                }
            }
        }

        // set default value or selected
        if ($key == $itemValue or ($item->getMode() == 'add' && $key == $item->getDefaultValue())) {
            $element->setAttributes('selected'); // add attribute selected
        }
        // parse element
        return $this->parseElement($element, 'option', true);
    }

    /**
     * create checkbox element
     * checkbox
     * @param MFormItem $item
     * @return $this
     * @author Joachim Doerr
     */
    private function generateCheckboxElement(MFormItem $item)
    {
        // default manipulations
        MFormItemManipulator::setVarAndIds($item); // transform ids for template usage
        MFormItemManipulator::setCustomId($item); // set optional custom id
        MFormItemManipulator::setDefaultClass($item); // set default class for r5 mform default theme

        $checkboxElements = '';

        // options must te be given
        if (sizeof($item->getOptions()) > 0) {
            // is multiple flag true
            // if ($item->isMultiple()) {
                // TODO add hidden field and javascript and so fare
                // TODO add text element
            // } else {
                foreach ($item->getOptions() as $key => $value) {
                    $checkboxElements .= $this->createCheckElement($item, $key, $value); // create element by helper
                    break;
                }
            //}
        }
        // create label element
        $label = new MFormElement();
        $label->setId($item->getId())
            ->setValue($item->getLabel());

        // create templateElement object
        $templateElement = new MFormElement();
        $templateElement->setLabel($this->parseElement($label, 'label', true))
            ->setElement($checkboxElements);

        // add to output element array
        $this->elements[] = $this->parseElement($templateElement, 'default');
        return $this;
    }

    /**
     * helper method to create checkbox and radiobutton elements
     * checkbox, radiobutton
     * @param MFormItem $item
     * @param $key
     * @param $value
     * @param null|int $count
     * @return mixed
     * @author Joachim Doerr
     */
    private function createCheckElement(MFormItem $item, $key, $value, $count = null)
    {
        // create element
        $element = new MFormElement();
        $element->setValue($key)
            ->setId($item->getId())
            ->setVarId($item->getVarId())
            ->setType($item->getType())
            ->setLabel($value);

        // add count to id
        if (is_numeric($count)) {
            $element->setId($item->getId() . $count);
        }
        // set checked by value or default value
        if ($key == $item->getValue() or ($item->getMode() == 'add' && $key == $item->getDefaultValue())) {
            $element->setAttributes('checked="checked"');
        }
        // parse element
        return $this->parseElement($element, $item->getType(), true);
    }

    /**
     * create radiobutton element
     * radiobutton
     * @param MFormItem $item
     * @return $this
     * @author Joachim Doerr
     */
    private function generateRadioElement(MFormItem $item)
    {
        // default manipulations
        MFormItemManipulator::setVarAndIds($item); // transform ids for template usage
        MFormItemManipulator::setCustomId($item); // set optional custom id
        MFormItemManipulator::setDefaultClass($item); // set default class for r5 mform default theme

        $radioElements = '';
        // options must te be given
        if (sizeof($item->getOptions()) > 0) {
            $count = 0; // init count
            foreach ($item->getOptions() as $key => $value) {
                $count++; // + count
                $radioElements .= $this->createCheckElement($item, $key, $value, $count); // create element by helper
            }
        }

        // create label element
        $label = new MFormElement();
        $label->setId($item->getId())
            ->setValue($item->getLabel());

        // create templateElement object
        $templateElement = new MFormElement();
        $templateElement->setLabel($this->parseElement($label, 'label', true))
            ->setElement($radioElements);

        // add to output element array
        $this->elements[] = $this->parseElement($templateElement, 'default');
        return $this;
    }

    /**
     * media, medialist
     * @param MFormItem $item
     * @return $this
     * @author Joachim Doerr
     */
    private function generateMediaElement(MFormItem $item)
    {
        // create label element
        $label = new MFormElement();
        $label->setId($item->getId())
            ->setValue($item->getLabel());

        // create templateElement object
        $templateElement = new MFormElement();
        $templateElement->setLabel($this->parseElement($label, 'label', true));

        switch ($item->getType()) {
            default:
            case 'media':
                $templateElement->setElement(rex_var_media::getWidget($item->getVarId()[0], 'REX_INPUT_MEDIA[' . $item->getVarId()[0] . ']', $item->getValue(), $item->getParameter()));
                break;
            case 'medialist':
                $templateElement->setElement(rex_var_medialist::getWidget($item->getVarId()[0], 'REX_INPUT_MEDIALIST[' . $item->getVarId()[0] . ']', $item->getValue(), $item->getParameter()));
                break;
        }

        // add to output element array
        $this->elements[] = $this->parseElement($templateElement, 'default');
        return $this;
    }

    /**
     * link, linklist
     * @param MFormItem $item
     * @return $this
     * @author Joachim Doerr
     */
    private function generateLinkElement(MFormItem $item)
    {
        // create label element
        $label = new MFormElement();
        $label->setId($item->getId())
            ->setValue($item->getLabel());

        // create templateElement object
        $templateElement = new MFormElement();
        $templateElement->setLabel($this->parseElement($label, 'label', true));

        switch ($item->getType()) {
            default:
            case 'link':
                $templateElement->setElement(rex_var_link::getWidget($item->getVarId()[0], 'REX_INPUT_LINK[' . $item->getVarId()[0] . ']', $item->getValue(), $item->getParameter()));
                break;
            case 'linklist':
                $templateElement->setElement(rex_var_linklist::getWidget($item->getVarId()[0], 'REX_INPUT_LINKLIST[' . $item->getVarId()[0] . ']', $item->getValue(), $item->getParameter()));
                break;
        }

        // add to output element array
        $this->elements[] = $this->parseElement($templateElement, 'default');
        return $this;
    }

    /**
     * @param MFormItem[] $items
     * @return $this
     * @author Joachim Doerr
     */
    private function parseFormFields(array $items)
    {
        if (sizeof($items) > 0) {
            foreach ($items as $key => $item) {

                switch ($item->getType()) {
                    case 'close-fieldset':
                        $this->closeFieldset();
                        break;
                    case 'fieldset':
                        $this->generateFieldset($item);
                        break;
                    case 'html':
                    case 'headline':
                    case 'description':
                        $this->generateLineElement($item);
                        break;
//                    case 'callback':
//                        $this->getCallbackElement($item);
//                        break;
                    case 'text':
                    case 'hidden':
                    case 'text-readonly':
                        $this->generateInputElement($item);
                        break;
                    case 'markitup':
                    case 'textarea':
                    case 'textarea-readonly':
                        $this->generateAreaElement($item);
                        break;
                    case 'select':
                    case 'multiselect':
                        $this->generateOptionsElement($item);
                        break;
                    case 'radio':
                        $this->generateRadioElement($item);
                        break;
                    case 'checkbox':
                    case 'multicheckbox':
                        $this->generateCheckboxElement($item);
                        break;
                    case 'link':
                    case 'linklist':
                        $this->generateLinkElement($item);
                        break;
                    case 'media':
                    case 'medialist':
                        $this->generateMediaElement($item);
                        break;
                }
            }
        }
        return $this;
    }

    /**
     * final parsing
     * @param MFormItem[] $items
     * @param null $theme
     * @param bool $debug
     * @return string
     * @author Joachim Doerr
     */
    public function parse(array $items, $theme = NULL, $debug = false)
    {
        $this->theme = rex_addon::get('mform')->getConfig('mform_theme');
        if (!is_null($theme) && $theme != $this->theme) {
            $this->theme = $theme;
            // asset not exist? add via boot check
            MFormThemeHelper::themeBootCheck($theme);
            // add css
            // use theme helper class
            if(sizeof(MFormThemeHelper::getCssAssets($this->theme)) > 0) {
                // foreach all css files
                foreach (MFormThemeHelper::getCssAssets($this->theme) as $css) {
                    // add assets css file
                   $this->elements[] = '<link rel="stylesheet" type="text/css" media="all" href="' . rex_url::addonAssets('mform', $css) . '" />';
                }
            }
        }

        $this->parseFormFields($items);

        // close fieldset
        if ($this->fieldset) {
            $this->closeFieldset();
        }
        // show for debug items
        if ($debug) {
            echo '<pre>'.PHP_EOL;
            print_r($items);
            echo '</pre>'.PHP_EOL;
        }
        return implode($this->elements);
    }
}
