<div id="icymobi-contact-config" class="tab-content">
	<div class="inner">
		<h2>Drop a pin for your business location</h2>
		<div class="googlefind">
			<input id="geocomplete" type="text" class="is_location" placeholder="Type in an address" />
		</div>
		<div class="map_canvas" style="height:400px;width: 700px;"></div>
		
		<table class="form-table mapdetail">

			<?php 

				$this->render_field('text', 'contact_map_lat', 			array('label'=> 'Latitude', 'attrs' => 'data-geo="lat"', 'hidden_row' => true));
				$this->render_field('text', 'contact_map_lng', 			array('label'=> 'Longitude', 'attrs' => 'data-geo="lng"', 'hidden_row' => true));
				$this->render_field('text', 'contact_map_title', 		array('label'=> 'Title'));
				$this->render_field('textarea', 'contact_map_content', 	array('label'=> 'Content'));

			?>
		</table>

	</div>
</div>