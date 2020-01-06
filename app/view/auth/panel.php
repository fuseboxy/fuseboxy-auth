<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$layout">
				<string name="content" />
			</structure>
			<structure name="$authLayout">
				<string name="flash" />
				<string name="logo" optional="yes" />
				<string name="title" optional="yes" />
				<string name="brand" optional="yes" />
			</structure>
		</in>
		<out />
	</io>
</fusedoc>
*/ ?>
<div id="auth-panel" class="container" style="margin-top: 50vh; transform: translateY(-60%);">
	<div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-5 offset-lg-3">
		<div class="card">
			<a href="<?php echo F::url($fusebox->controller); ?>" class="card-header btn"><?php
				// logo
				if ( !empty($authLayout['logo']) ) :
					?><img src="<?php echo $authLayout['logo']; ?>" class="float-left my-2" /><?php
				endif;
				// title
				if ( !empty($authLayout['title']) ) :
					?><h3><?php echo $authLayout['title']; ?></h3><?php
				endif;
				// brand
				if ( !empty($authLayout['brand']) ) :
					?><h5 class="text-muted"><?php echo $authLayout['brand']; ?></h5><?php
				endif;
			?></a>
			<div class="card-body"><?php
				// message
				if ( !empty($authLayout['flash']) ) echo $authLayout['flash'];
				// content
				if ( !empty($layout['content']) ) echo $layout['content'];
			?></div>
		</div>
	</div>
</div>