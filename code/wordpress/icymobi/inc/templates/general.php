<div id="icymobi-general" class="tab-content active">
	<h2>Maintenance Settings</h2>
	<table class="form-table form-general">
	
		<?php 
			$this->render_field('checkbox', 'general_enable_app', 	array('label'=> 'Maintenance Mode', 'sub_label'=> 'Enable Maintenance Mode'), 0);
			$this->render_field('textarea', 'general_maintenance_text', 	array('label'=> 'Maintenance Mode Text'));
		?>
		
	</table>
</div>