<?php
Class WPAS_Field {
	
	public $id;
	public $title;
	public $type;
	public $format;
	public $placeholder;
	public $values;
	public $selected = '';
	public $selected_r = array();

	function __construct($id, $args = array()) {
		$defaults = array(	'title' => '',
							'format' => 'select',
							'placeholder' => false,
							'values' => array()
							);

		$this->id = $id;
		extract(wp_parse_args($args,$defaults));
		$this->title = $title;
		$this->type = $type;
		$this->format = $format;
		$this->values = $values;
		$this->placeholder = $placeholder;
		
		if (empty($values) && isset($value)) {
			$this->values = $value;
		}
		
		if(isset($_REQUEST[$id])) {
			$this->selected = $_REQUEST[$id];
			$this->selected_r = $_REQUEST[$id];
		}

		if (!is_array($this->selected)) {
	    	$this->selected_r = explode(',',$this->selected);
	    }

	}

	function build_field() {
		if ($this->format != 'hidden') {
			echo '<div id="wpas-'.$this->id.'" class="wpas-'.$this->id.' wpas-'.$this->type.'-field  wpas-field">';
			if ($this->title) {
				echo '<div class="label-container"><label for="'.$this->id.'">'.$this->title.'</label></div>';
			}
		}
	 	switch($this->format) {
	 		case ('select') :
	 			$this->select();
	 			break;
	 		case ('multi-select') :
	 			$this->select(true);
	 			break;
	 		case ('checkbox') :
	 			$this->checkbox();
	 			break;
	 		case ('radio') :
	 			$this->radio();
	 			break;
	 		case ('text') :
	 			$this->text();
	 			break;
	 		case ('textarea') :
	 			$this->textarea();
	 			break;
	 		case ('html') :
	 			$this->html();
	 			break;
	 		case ('hidden') :
	 			$this->hidden();
	 			break;
	 		case ('submit') :
	 			$this->submit();
	 			break;
	 	}
		if ($this->format != 'hidden') {
		 echo '</div>';
		}
	}

	function select($multi = false) {

	    	if ($multi) {
	    		$multiple = ' multiple="multiple"';
	    	} else {
	    		$multiple = '';
	    	}

			echo '<select id="'.$this->id.'" name="'.$this->id;
			if ($multi) {
				echo '[]';
			}
			echo  '"'.$multiple.'>';

			foreach ($this->values as $value => $label) {	
				$value = esc_attr($value);
				$label = esc_attr($label);
				echo '<option value="'.$value.'"';

					if (in_array($value, $this->selected_r)) {
						echo ' selected="selected"';
					}

				echo '>'.$label.'</option>';
			}

			echo '</select>';
	}

	function checkbox() {
		echo '<div class="wpas-'.$this->id.'-checkboxes wpas-checkboxes field-container">';
		$ctr = 1;
		foreach ($this->values as $value => $label) {
			$value = esc_attr($value);
			$label = esc_attr($label);
			echo '<div class="wpas-'.$this->id.'-checkbox-'.$ctr.'-container wpas-'.$this->id.'-checkbox-container wpas-checkbox-container">';
			echo '<input type="checkbox" id="wpas-'.$this->id.'-checkbox-'.$ctr.'" class="wpas-'.$this->id.'-checkbox wpas-checkbox" name="'.$this->id.'[]" value="'.$value.'"';
				if (in_array($value, $this->selected_r)) {
					echo ' checked="checked"';
				}
			echo '>';
			echo '<label for="wpas-'.$this->id.'-checkbox-'.$ctr.'"> '.$label.'</label></div>';
			$ctr++;
		}
		echo '</div>';		
	}

	function radio() {
		echo '<div class="wpas-'.$this->id.'-radio-buttons wpas-radio-buttons field-container">';
		$ctr = 1;
		foreach ($this->values as $value => $label) {
			$value = esc_attr($value);
			$label = esc_attr($label);
			echo '<div class="wpas-'.$this->id.'-radio-'.$ctr.'-container wpas-'.$this->id.'-radio-container wpas-radio-container">';
			echo '<input type="radio" id="wpas-'.$this->id.'-radio-'.$ctr.'" class="wpas-'.$this->id.'-radio wpas-radio" name="'.$this->id.'" value="'.$value.'"';
				if (in_array($value, $this->selected_r)) {
					echo ' checked="checked"';
				}
			echo '>';
			echo '<label for="wpas-'.$this->id.'-radio-'.$ctr.'"> '.$label.'</label></div>';
			$ctr++;
		}
		echo '</div>';		
	}

	function text() {
    	if (is_array($this->selected)) {
    		if (isset($this->selected[0]))
    			$value = $this->selected[0];
    		else
    			$value = '';
    	} elseif (isset($this->selected)) {
    		$value = $this->selected;
    	} elseif (is_array($this->values)) {
    		$value = reset($this->values);
    	} else {
    		$value = $this->values;
    	}
    	$value = esc_attr($value);
    	$placeholder = '';
    	if ($this->placeholder)
    		$placeholder = ' placeholder="'.$this->placeholder.'"';
    	echo '<input type="text" id="'.$this->id.'" value="'.$value.'" name="'.$this->id.'"'.$placeholder.'>';
	}

	function textarea() {
    	if (is_array($this->selected)) {
    		if (isset($this->selected[0]))
    			$value = $this->selected[0];
    		else
    			$value = '';
    	} elseif (isset($this->selected)) {
    		$value = $this->selected;
    	} elseif (is_array($this->values)) {
    		$value = reset($this->values);
    	} else {
    		$value = $this->values;
    	}
    	$value = esc_textarea($value);
    	$placeholder = '';
    	if ($this->placeholder)
    		$placeholder = ' placeholder="'.$this->placeholder.'"';
    	echo '<textarea id="'.$this->id.'" name="'.$this->id.'"'.$placeholder.'>'.$value.'</textarea>';		
	}

	function submit() {
		echo '<input type="submit" value="'.esc_attr($this->values).'">';
	}

	function html() {
		echo $this->values;
	}

	function hidden() {
		$value = $this->values;
		if (is_array($value)) {
    		$value = reset($value);
    	} 
    	$value = esc_attr($value);
		echo '<input type="hidden" name="'.$this->id.'" value="'.$value.'">';
	}

} // Class