<div id="icymobi-add-ons" class="tab-content wc_addons_wrap">
	<div class="inner">
		<ul class="products">
			<?php foreach ($products as $product) { ?>
			<li class="product">
				<a href="<?php echo esc_attr($product->link); ?>">
					<?php if($product->image == ''){ ?>
						<h2><?php echo $product->title; ?></h2>
					<?php }else{ ?>
						<img src="<?php echo esc_url($product->image) ?>">
					<?php } ?>
					<?php  ?>
					<span class="price"><?php echo $product->price; ?></span>
					<p><?php echo $product->excerpt; ?></p>
				</a>
			</li>
			<?php } ?>
		</ul>
	</div>
</div>

