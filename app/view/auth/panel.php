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
				<string name="brand" optional="yes" />
				<string name="title" optional="yes" />
			</structure>
		</in>
		<out />
	</io>
</fusedoc>
*/ ?>
<div id="auth-panel" class="container" style="margin-top: 45vh; transform: translateY(-60%);">
	<div class="col-12 col-sm-10 col-md-8 col-lg-6 offset-sm-1 offset-md-2 offset-lg-3"><?php
		// logo
		if ( !empty($authLayout['logo']) ) :
			?><div class="logo"><img src="<?php echo $authLayout['logo']; ?>" class="d-block mx-auto mb-4" /></div><?php
		endif;
		?><div class="card">
			<a href="<?php echo F::url($fusebox->controller); ?>" class="card-header btn"><?php
				// brand
				if ( !empty($authLayout['brand']) ) :
					?><h3><?php echo $authLayout['brand']; ?></h3><?php
				endif;
				// title
				if ( !empty($authLayout['title']) ) :
					?><h5 class="text-muted"><?php echo $authLayout['title']; ?></h5><?php
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