<?php

namespace Bsik\Forms;


# load the remaining default classes
require_once BSIK_AUTOLOAD;

use \Bsik\Formr\GenerateForm;

class BootstrapForms extends GenerateForm
{
    public $container;
    public static $instance;
    public function __construct()
    {
        self::$css_config = [
            'div' => 'form-group',
            'label' => 'control-label',
            'input' => 'form-control',
            'file' => 'form-control-file',
            'help' => 'form-text',
            'button' => 'btn',
            'button-primary' => 'btn btn-primary',
            'warning' => 'has-warning',
            'error' => 'invalid-feedback',
            'text-error' => 'text-danger',
            'success' => 'has-success',
            'checkbox' => 'form-check',
            'checkbox-label' => 'form-check-label',
            'checkbox-inline' => 'form-check form-check-inline',
            'form-check-input' => 'form-check-input',
            'radio' => 'form-check',
            'link' => 'alert-link',
            'list-ul' => 'list-unstyled',
            'list-ol' => 'list-unstyled',
            'list-dl' => 'list-unstyled',
            'alert-e'=> 'alert alert-danger',
            'alert-w' => 'alert alert-warning',
            'alert-s' => 'alert alert-success',
            'alert-i' => 'alert alert-info',
            'is-invalid' => 'is-invalid',
        ];
        //add reflection:
        //$method_to_use = new \ReflectionMethod(__CLASS__."::use_wrapper");
        //formatting: inserts a new line (\n)
        $this->nl = $this->_nl(1);
        
        //formatting: inserts a tab (\t)
        $this->t = $this->_t(1);
    }

    # bootstrap 4 field wrapper
    public function wrapper($element = '', $data = '')
    {
        if (empty($data)) {
            return false;
        }

        # if an ID is not present, create one using the name field
        $data['id'] = $this->make_id($data);

        # set the label array value to null if a label is not present
        if(!isset($data['label'])) {
            $data['label'] = null;
        }

        # create our $return variable
        $return = null;

        if ($this->type_is_checkbox($data))
        {
            # input is a checkbox or radio
            # don't print the label if we're printing an array
            if (! $this->is_array($data['value']))
            {
                # add an ID to the enclosing div so that we may target it with javascript if required
                $return = $this->nl.'<div id="_'.$data['id'].'" class="';

                # inline checkbox
                if (!empty($data['checkbox-inline'])) {
                    $return .= static::defined_css('checkbox-inline');
                } else {
                    $return .= static::defined_css('checkbox');
                }
            } else {
                # open the form group div
                $return = $this->nl.'<div id="_'.$data['id'].'" class="'.static::defined_css('div').'">';
            }
        } else {
            # open the form group div tag. note that we may be adding additional attributes...
            $return = $this->nl.'<div id="_'.$data['id'].'" class="'.static::defined_css('div');
        }

        if (! $this->is_array($data['value'])) {
            # no additional attributes
            $return .= '">';
        }

        # add the field element here (before the label) if checkbox or radio
        if ($this->type_is_checkbox($data)) {
            $return .= $this->nl.$this->t.$element;
        }

        # if the label is empty add .sr-only, otherwise...
        if ($this->is_not_empty($data['label'])) {
            if($this->type_is_checkbox($data)) {
                $label_class = static::defined_css('checkbox-label');
            } else {
                $label_class = static::defined_css('label');
            }
        } else {
            $label_class = 'sr-only';
        }

        # see if we're in a checkbox array...
        if ($this->is_array($data['name'])) {
            # we are. we don't want to color each checkbox label if there's an error - we only want to color the main label for the group
            # we'll add the label text later...
            $return .= $this->t.'<label for="'.$data['id'].'">'.$this->nl;
        } else {
            # we are not in an array
            if ($this->type_is_checkbox($data)) {
                # no default class on a checkbox or radio
                if($this->is_not_empty($data['label'])) {
                    # open the label, but don't insert the label text here; we're doing it elsewhere
                    $return .= $this->nl.$this->t.'<label class="'.$label_class.'" for="'.$data['id'].'">';
                }
            } else {
                # open the label and insert the label text
                $return .= $this->nl.$this->t.'<label class="'.$label_class.'" for="'.$data['id'].'">'.$data['label'];
            }
        }

        # add a required field indicator (*)
        if ($this->_check_required($data['name']) && $this->is_not_empty($data['label'])) {
            if (! $this->type_is_checkbox($data)) {
                $return .= $this->required_indicator;
            }
        }

        # close the label if NOT a checkbox or radio
        if (! $this->type_is_checkbox($data)) {
            $return .= '</label>'.$this->nl;
        }

        # add the field element here if NOT a checkbox or radio
        if (! $this->type_is_checkbox($data)) {
            $return .= $this->t.$element;
        }

        # inline help text
        if (!empty($data['inline']))
        {
            # help-block text
            # if the text is surrounded by square brackets, show only on form error
            if ($this->is_in_brackets($data['inline'])) {
                if ($this->in_errors($data['name'])) {
                    # trim the brackets and show on error
                    $return .= $this->nl.$this->t.'<p class="'.static::defined_css('help').'">'.trim($data['inline'], '[]').'</p>';
                }
            } else {
                # show this text on page load
                $return .= $this->nl.$this->t.'<p class="'.static::defined_css('help').'">'.$data['inline'].'</p>';
            }
        }

        # checkbox/radio: add the label text and close the label tag
        if ($this->is_not_empty($data['label']) && $this->type_is_checkbox($data))
        {
            # add label text
            $return .= ' '.$data['label'];
            
            # add a required field indicator (*)
            if ($this->_check_required($data['name']) && $this->is_not_empty($data['label'])) {
                $return .= $this->required_indicator;
            }

            # close the label tag
            $return .= $this->nl.$this->t.'</label>'.$this->nl;
            
            # close the controls div
            $return .= '</div>'.$this->nl;
        } else {
            # close the controls div with additional formatting
            $return .= $this->nl.'</div>'.$this->nl;
        }

        return $return;
    }
}
