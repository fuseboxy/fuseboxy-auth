<?php /*
<fusedoc>
	<io>
		<in>
			<structure name="$layout">
				<string name="panelTitle" />
				<string name="panelSubtitle" />
			</structure>
			<structure name="$authLayout">
				<string name="flash" />
			</structure>
			<structure name="$layout">
				<string name="content" />
			</structure>
		</in>
		<out />
	</io>
</fusedoc>
*/

?>
<div id="auth-panel" class="container" style="margin-top: 50vh; transform: translateY(-60%);">
	<div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-4 offset-lg-4">
		<div class="card panel-default">
			<div class="card-header text-center">
				<h2><?php echo $layout['panelTitle']; ?></h2>
				<h5 class="text-muted"><?php echo $layout['panelSubtitle']; ?></h5>
			</div>
			<div class="card-body"><?php
				if ( isset($authLayout['flash']) ) echo $authLayout['flash'];
				if ( isset($layout['content']) ) echo $layout['content'];
			?></div>
		</div>
	</div>
</div>