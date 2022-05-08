<?php

class Inspius_Icymobi_Option{

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function get_option($id, $default = ''){
		$options = get_option( 'icymobi_config_option', array());
		if(isset($options[$id]) && $options[$id]!='')
			return $options[$id];
		else
			return $default;
	}

	public function render_field($type, $id, $attrs = array('label'=>'', 'sub_label'=>'', 'attrs' => '', 'hidden_row'=>false), $default = ''){
		$html = '<tr'.(($attrs['hidden_row'])?' style="display:none;"':'').'>
					<th scope="row">
						<label for="'.esc_attr($id).'">'. $attrs["label"] .'</label>
					</th>
					<td>
			';
		switch ($type) {
			case 'text':
				$html .= '<input '.$attrs["attrs"].' name="'.esc_attr($id).'" id="'.esc_attr($id).'" type="text" class="regular-text" value="'. esc_attr($this->get_option( $id, $default )) .'">';
				break;
			case 'textarea':
				$html .= '<textarea '.$attrs["attrs"].' name="'.esc_attr($id).'" id="'.esc_attr($id).'" columns="30" rows="10" >'.$this->get_option( $id, $default ).'</textarea>';
				break;
			case 'checkbox':
				$html .= '
						<label for="'.esc_attr($id).'">
							<input name="'.esc_attr($id).'" id="'.esc_attr($id).'" type="checkbox" value="1" '.checked( $this->get_option($id, $default), 1, false ).'> 
							'.$attrs["sub_label"].'							
						</label>
				';
				break;
		}
		if(isset($attrs['description']) && $attrs['description']!=''){
			$html .= '<p class="description">'.$attrs['description'].'</p>';
		}
		$html .= '</td></tr>';

		echo $html;
	}
}
